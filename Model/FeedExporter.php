<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Haerriz\GoogleShoppingFeed\Model\Artifact\ArtifactManager;
use Haerriz\GoogleShoppingFeed\Model\Artifact\CurrentArtifactPublisher;
use Haerriz\GoogleShoppingFeed\Model\Mapping\RowBuilder;
use Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedJob as JobResource;
use Haerriz\GoogleShoppingFeed\Model\Storage\AdapterPool;
use Haerriz\GoogleShoppingFeed\Model\Writer\Pool as WriterPool;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class FeedExporter
{
    private const BATCH_SIZE = 500;

    private $filesystem;
    private $productProvider;
    private $productTypeResolver;
    private $rowBuilder;
    private $writerPool;
    private $ruleFactory;
    private $jobResource;
    private $adapterPool;
    private $artifactManager;
    private $artifactPublisher;
    private $feedLogHandler;
    private $logger;

    public function __construct(
        Filesystem $filesystem,
        ProductProviderInterface $productProvider,
        ProductTypeResolverInterface $productTypeResolver,
        RowBuilder $rowBuilder,
        WriterPool $writerPool,
        RuleFactory $ruleFactory,
        JobResource $jobResource,
        AdapterPool $adapterPool,
        ArtifactManager $artifactManager,
        CurrentArtifactPublisher $artifactPublisher,
        FeedLogHandler $feedLogHandler,
        LoggerInterface $logger
    ) {
        $this->filesystem          = $filesystem;
        $this->productProvider     = $productProvider;
        $this->productTypeResolver = $productTypeResolver;
        $this->rowBuilder          = $rowBuilder;
        $this->writerPool          = $writerPool;
        $this->ruleFactory         = $ruleFactory;
        $this->jobResource         = $jobResource;
        $this->adapterPool         = $adapterPool;
        $this->artifactManager     = $artifactManager;
        $this->artifactPublisher   = $artifactPublisher;
        $this->feedLogHandler      = $feedLogHandler;
        $this->logger              = $logger;
    }

    public function export(FeedProfileInterface $profile, $outputPath, FeedJob $job = null, $limit = null)
    {
        // Strip any accidental pub/media prefix — Magento FS root is already pub/media/
        $outputPath = preg_replace('#^pub/media/#', '', ltrim((string)$outputPath, '/'));

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream    = null;
        $startTime = microtime(true);

        $rule          = $this->createRule($profile);
        $mappings      = $this->rowBuilder->getMappings($profile);
        $mappingErrors = $this->rowBuilder->validate($profile);

        if ($mappingErrors) {
            throw new \InvalidArgumentException(implode(' ', $mappingErrors));
        }

        $fields = array_map(static function (array $mapping) {
            return (string)($mapping['google_attribute'] ?? $mapping['field'] ?? '');
        }, $mappings);

        if (!$fields || in_array('', $fields, true) || count($fields) !== count(array_unique($fields))) {
            throw new \InvalidArgumentException('Mappings require unique, non-empty output fields.');
        }

        $writer            = $this->writerPool->get($profile->getFeedType());
        $selectedColl      = $this->productProvider->getCollection($profile, $rule, 0, 1);
        $selected          = (int)$selectedColl->getSize();
        $processed = $exported = $skipped = $invalid = $warnings = 0;
        $lastEntityId      = 0;

        $this->feedLogHandler->log($job, 'info', "Starting export for profile [{$profile->getName()}]: selected={$selected}");

        try {
            $stream = $directory->openFile($outputPath, 'w+');
            $stream->lock();
            $writer->start($stream, $profile, $fields);

            while ($limit === null || $exported < $limit) {
                $collection = $this->productProvider->getCollection(
                    $profile, $rule, $lastEntityId, self::BATCH_SIZE
                );
                if ($collection->count() === 0) {
                    break;
                }

                $this->productTypeResolver->prepare($collection, $profile);

                foreach ($collection as $product) {
                    $lastEntityId = (int)$product->getId();
                    $processed++;

                    if ($rule && !$rule->getConditions()->validate($product)) {
                        $skipped++;
                        continue;
                    }

                    foreach ($this->productTypeResolver->resolve($product, $profile) as $feedProduct) {
                        if ($limit !== null && $exported >= $limit) {
                            break 2;
                        }
                        try {
                            $row = $this->rowBuilder->build($feedProduct, $profile);
                            $writer->writeRow($stream, $profile, $row);
                            $exported++;
                        } catch (\Exception $rowEx) {
                            $warnings++;
                            $this->feedLogHandler->log($job, 'warning', "SKU [{$feedProduct->getSku()}]: {$rowEx->getMessage()}");
                        }
                    }
                }

                $collection->clear();
                $this->updateJob($job, $selected, $processed, $exported, $skipped, $invalid, $warnings);
            }

            $writer->finish($stream, $profile);

        } finally {
            if ($stream) {
                $stream->unlock();
                $stream->close();
            }
        }

        // Calculate file metrics
        $absolutePath = $directory->getAbsolutePath($outputPath);
        $fileSize     = file_exists($absolutePath) ? filesize($absolutePath) : 0;
        $checksum     = file_exists($absolutePath) ? hash_file('sha256', $absolutePath) : '';
        $duration     = round(microtime(true) - $startTime, 3);

        // Update job with final metrics
        if ($job) {
            $job->setFileSize($fileSize);
            $job->setChecksum($checksum);
            $job->setDuration($duration);
            $job->setPeakMemory(memory_get_peak_usage(true));
        }
        $this->updateJob($job, $selected, $processed, $exported, $skipped, $invalid, $warnings);

        // Record artifact (immutable history)
        $this->artifactManager->record($profile, $absolutePath, $fileSize, $checksum, $exported);

        // Publish current artifact pointer
        $this->artifactPublisher->publish($profile, $absolutePath);

        // Deliver via configured adapter (Local/FTP/SFTP)
        try {
            $this->adapterPool->deliver($profile, $absolutePath);
        } catch (\Exception $deliveryEx) {
            $this->logger->warning("Feed delivery failed for [{$profile->getName()}]: " . $deliveryEx->getMessage());
            $this->feedLogHandler->log($job, 'warning', "Delivery failed: " . $deliveryEx->getMessage());
        }

        $this->feedLogHandler->log($job, 'info', "Export complete: exported={$exported}, skipped={$skipped}, warnings={$warnings}, size={$fileSize}B, duration={$duration}s");
        $this->logger->info("GoogleShoppingFeed [{$profile->getName()}]: exported={$exported} products to {$outputPath} ({$fileSize}B)");

        return compact('selected', 'processed', 'exported', 'skipped', 'invalid', 'warnings', 'fileSize', 'checksum', 'duration');
    }

    private function createRule(FeedProfileInterface $profile)
    {
        $serialized = $profile->getConditionsSerialized();
        if (!$serialized) return null;
        $conditions = json_decode($serialized, true);
        if (!$conditions) return null;
        $rule = $this->ruleFactory->create();
        $rule->getConditions()->loadArray($conditions);
        return $rule;
    }

    private function updateJob($job, $selected, $processed, $exported, $skipped, $invalid, $warnings = 0)
    {
        if (!$job) return;
        $job->setSelectedCount($selected);
        $job->setTotalProducts($selected);
        $job->setProcessedProducts($processed);
        $job->setExportedCount($exported);
        $job->setSkippedCount($skipped);
        $job->setErrorCount($invalid);
        $job->setWarningCount($warnings);
        $this->jobResource->save($job);
    }
}
