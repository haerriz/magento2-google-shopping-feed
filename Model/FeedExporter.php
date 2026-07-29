<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Haerriz\GoogleShoppingFeed\Model\Mapping\RowBuilder;
use Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedJob as JobResource;
use Haerriz\GoogleShoppingFeed\Model\Writer\Pool as WriterPool;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

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

    public function __construct(
        Filesystem $filesystem,
        ProductProviderInterface $productProvider,
        ProductTypeResolverInterface $productTypeResolver,
        RowBuilder $rowBuilder,
        WriterPool $writerPool,
        RuleFactory $ruleFactory,
        JobResource $jobResource
    ) {
        $this->filesystem = $filesystem;
        $this->productProvider = $productProvider;
        $this->productTypeResolver = $productTypeResolver;
        $this->rowBuilder = $rowBuilder;
        $this->writerPool = $writerPool;
        $this->ruleFactory = $ruleFactory;
        $this->jobResource = $jobResource;
    }

    public function export(FeedProfileInterface $profile, $outputPath, FeedJob $job = null, $limit = null)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream = null;
        $rule = $this->createRule($profile);
        $mappings = $this->rowBuilder->getMappings($profile);
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

        $writer = $this->writerPool->get($profile->getFeedType());
        $selectedCollection = $this->productProvider->getCollection($profile, $rule, 0, 1);
        $selected = (int)$selectedCollection->getSize();
        $processed = 0;
        $exported = 0;
        $skipped = 0;
        $invalid = 0;
        $lastEntityId = 0;

        try {
            $stream = $directory->openFile($outputPath, 'w+');
            $stream->lock();
            $writer->start($stream, $profile, $fields);
            while ($limit === null || $exported < $limit) {
                $collection = $this->productProvider->getCollection(
                    $profile,
                    $rule,
                    $lastEntityId,
                    self::BATCH_SIZE
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
                        $writer->writeRow($stream, $profile, $this->rowBuilder->build($feedProduct, $profile));
                        $exported++;
                    }
                }
                $collection->clear();
                $this->updateJob($job, $selected, $processed, $exported, $skipped, $invalid);
            }
            $writer->finish($stream, $profile);
        } finally {
            if ($stream) {
                $stream->unlock();
                $stream->close();
            }
        }
        $this->updateJob($job, $selected, $processed, $exported, $skipped, $invalid);
        return compact('selected', 'processed', 'exported', 'skipped', 'invalid');
    }

    private function createRule(FeedProfileInterface $profile)
    {
        $serialized = $profile->getConditionsSerialized();
        if (!$serialized) {
            return null;
        }
        $conditions = json_decode($serialized, true);
        if (!$conditions) {
            return null;
        }
        $rule = $this->ruleFactory->create();
        $rule->getConditions()->loadArray($conditions);
        return $rule;
    }

    private function updateJob($job, $selected, $processed, $exported, $skipped, $invalid)
    {
        if (!$job) {
            return;
        }
        $job->setSelectedCount($selected);
        $job->setTotalProducts($selected);
        $job->setProcessedProducts($processed);
        $job->setExportedCount($exported);
        $job->setSkippedCount($skipped);
        $job->setErrorCount($invalid);
        $this->jobResource->save($job);
    }
}
