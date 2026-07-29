<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Haerriz\GoogleShoppingFeed\Model\Modifier\Pool as ModifierPool;

use Haerriz\GoogleShoppingFeed\Model\Storage\AdapterPool;

use Haerriz\GoogleShoppingFeed\Model\RuleFactory;
use Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedJob as JobResource;
use Haerriz\GoogleShoppingFeed\Model\Logger\Sanitizer;

class FeedGenerator
{
    const BATCH_SIZE = 500;

    /**
     * @var ProductProviderInterface
     */
    protected $productProvider;

    /**
     * @var ProductTypeResolverInterface
     */
    private $productTypeResolver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ModifierPool
     */
    protected $modifierPool;

    /**
     * @var AdapterPool
     */
    protected $adapterPool;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param ProductProviderInterface $productProvider
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ModifierPool $modifierPool
     * @param AdapterPool $adapterPool
     * @param RuleFactory $ruleFactory
     */
    /**
     * @var FeedJobFactory
     */
    protected $jobFactory;

    /**
     * @var JobResource
     */
    protected $jobResource;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ModifierPool $modifierPool
     * @param AdapterPool $adapterPool
     * @param RuleFactory $ruleFactory
     * @param FeedJobFactory $jobFactory
     * @param JobResource $jobResource
     */
    /**
     * @var \Haerriz\GoogleShoppingFeed\Model\Url\UtmBuilder
     */
    protected $utmBuilder;

    /**
     * @var Sanitizer
     */
    private $sanitizer;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ModifierPool $modifierPool
     * @param AdapterPool $adapterPool
     * @param RuleFactory $ruleFactory
     * @param FeedJobFactory $jobFactory
     * @param JobResource $jobResource
     * @param \Haerriz\GoogleShoppingFeed\Model\Url\UtmBuilder $utmBuilder
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        ProductTypeResolverInterface $productTypeResolver,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        ModifierPool $modifierPool,
        AdapterPool $adapterPool,
        RuleFactory $ruleFactory,
        FeedJobFactory $jobFactory,
        JobResource $jobResource,
        \Haerriz\GoogleShoppingFeed\Model\Url\UtmBuilder $utmBuilder,
        Sanitizer $sanitizer
    ) {
        $this->productProvider = $productProvider;
        $this->productTypeResolver = $productTypeResolver;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->modifierPool = $modifierPool;
        $this->adapterPool = $adapterPool;
        $this->ruleFactory = $ruleFactory;
        $this->jobFactory = $jobFactory;
        $this->jobResource = $jobResource;
        $this->utmBuilder = $utmBuilder;
        $this->sanitizer = $sanitizer;
    }

    public function generate(FeedProfileInterface $profile, $triggerSource = 'cron')
    {
        $startTime = microtime(true);
        $type = $profile->getFeedType();
        $filename = $profile->getFilename();
        $correlationId = bin2hex(random_bytes(16));
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $localPath = 'google_feed/' . $filename;
        $temporaryPath = 'google_feed/.' . $filename . '.' . $correlationId . '.tmp';
        $result = false;
        $generated = false;

        // Instantiate job tracing entry
        $job = $this->jobFactory->create();
        $job->setProfileId($profile->getId());
        $job->setStatus('running');
        $job->setTriggerSource($triggerSource);
        $job->setStartedAt(date('Y-m-d H:i:s'));
        $job->setData('correlation_id', $correlationId);
        $job->setData('format', $type);
        $job->setData('artifact_path', $localPath);
        $job->setData('profile_snapshot', json_encode($this->getSafeProfileSnapshot($profile)));
        $this->jobResource->save($job);

        try {
            if ($type === 'csv') {
                $this->generateCsv($profile, $temporaryPath, $job);
            } elseif ($type === 'xml') {
                $this->generateXml($profile, $temporaryPath, $job);
            } else {
                throw new \InvalidArgumentException('Unsupported feed format.');
            }

            $directory->renameFile($temporaryPath, $localPath);
            $generated = true;
            $absolutePath = $directory->getAbsolutePath($localPath);
            $job->setFileSize($directory->stat($localPath)['size']);
            $job->setChecksum(hash_file('sha256', $absolutePath));

            $deliveryType = $profile->getDeliveryType() ?: 'local';
            $adapter = $this->adapterPool->get($deliveryType);
            $result = (bool)$adapter->upload($profile, $localPath);
            $job->setStatus($result ? 'success' : 'partial');
            $job->setDeliveryResult($result ? 'Delivered successfully' : 'Delivery failed');
        } catch (\Throwable $exception) {
            $safeMessage = $this->sanitizer->sanitize($exception->getMessage());
            $job->setStatus($generated ? 'partial' : 'failed');
            $job->setData('failure_category', $generated ? 'delivery' : 'generation');
            $job->setData('failure_message', mb_substr($safeMessage, 0, 1000));
            $job->setData('exception_class', get_class($exception));
            $job->setDeliveryResult(__('Operation failed. Correlation ID: %1', $correlationId));
            $result = false;
        } finally {
            if ($directory->isExist($temporaryPath)) {
                $directory->delete($temporaryPath);
            }

            $job->setDuration(round(microtime(true) - $startTime, 2));
            $job->setPeakMemory(memory_get_peak_usage(true));
            $job->setFinishedAt(date('Y-m-d H:i:s'));
            $this->jobResource->save($job);
        }

        return $result;
    }

    /**
     * Return a job snapshot that cannot expose persisted credentials.
     *
     * @param FeedProfileInterface $profile
     * @return array
     */
    private function getSafeProfileSnapshot(FeedProfileInterface $profile)
    {
        return [
            'profile_id' => $profile->getId(),
            'store_id' => $profile->getStoreId(),
            'currency' => $profile->getCurrency(),
            'filename' => $profile->getFilename(),
            'feed_type' => $profile->getFeedType(),
            'delivery_type' => $profile->getDeliveryType(),
            'attributes_mapping_serialized' => $profile->getAttributesMappingSerialized(),
            'conditions_serialized' => $profile->getConditionsSerialized(),
        ];
    }

    /**
     * Get base product collection with filters
     *
     * @param FeedProfileInterface $profile
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection(FeedProfileInterface $profile, $rule = null)
    {
        return $this->productProvider->getCollection($profile, $rule, 0, self::BATCH_SIZE);
    }

    /**
     * Generate CSV feed with batching and streaming
     *
     * @param FeedProfileInterface $profile
     * @param string $filename
     * @param \Haerriz\GoogleShoppingFeed\Model\FeedJob|null $job
     * @return bool
     */
    protected function generateCsv(FeedProfileInterface $profile, $outputPath, $job = null)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream = null;
        try {
            $stream = $directory->openFile($outputPath, 'w+');
            $stream->lock();

            $mapping = json_decode($profile->getAttributesMappingSerialized(), true) ?? [];
            $headers = [];
            foreach ($mapping as $map) {
                $headers[] = $map['google_attribute'];
            }
            $stream->writeCsv($headers);

        // Load rules if set
        $rule = null;
        $serializedConditions = $profile->getConditionsSerialized();
        if ($serializedConditions) {
            $conditions = json_decode($serializedConditions, true);
            if (!empty($conditions)) {
                $rule = $this->ruleFactory->create();
                $rule->getConditions()->loadArray($conditions);
            }
        }

        // Paginate and process products
        $lastEntityId = 0;
        $selected = 0;
        $processed = 0;
        $exported = 0;
        $skipped = 0;

        // Get total catalog count for selected
        $totalCollection = $this->getProductCollection($profile, $rule);
        $selected = $totalCollection->getSize();
        if ($job) {
            $job->setSelectedCount($selected);
            $job->setTotalProducts($selected);
        }

        while (true) {
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
                    $row = [];
                    foreach ($mapping as $map) {
                        $value = $this->resolveFeedValue($map, $feedProduct, $profile);
                        $value = $this->applyModifier($value, $map['modifier'] ?? '', $feedProduct);
                        $row[] = $value;
                    }
                    $stream->writeCsv($row);
                    $exported++;
                }
            }

            if ($job) {
                $job->setProcessedProducts($processed);
                $job->setExportedCount($exported);
                $job->setSkippedCount($skipped);
                $this->jobResource->save($job);
            }

            $collection->clear();
        }

            return true;
        } finally {
            if ($stream) {
                $stream->unlock();
                $stream->close();
            }
        }
    }

    /**
     * Generate XML feed with batching and streaming
     *
     * @param FeedProfileInterface $profile
     * @param string $filename
     * @param \Haerriz\GoogleShoppingFeed\Model\FeedJob|null $job
     * @return bool
     */
    protected function generateXml(FeedProfileInterface $profile, $outputPath, $job = null)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream = null;
        try {
            $stream = $directory->openFile($outputPath, 'w+');
            $stream->lock();

        // Write XML Header
        $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlHeader .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xmlHeader .= '<channel>' . "\n";
        $xmlHeader .= '<title><![CDATA[' . $profile->getName() . ']]></title>' . "\n";
        $xmlHeader .= '<link><![CDATA[' . $this->storeManager->getStore($profile->getStoreId())->getBaseUrl() . ']]></link>' . "\n";
        $stream->write($xmlHeader);

        $mapping = json_decode($profile->getAttributesMappingSerialized(), true) ?? [];
        
        // Load rules if set
        $rule = null;
        $serializedConditions = $profile->getConditionsSerialized();
        if ($serializedConditions) {
            $conditions = json_decode($serializedConditions, true);
            if (!empty($conditions)) {
                $rule = $this->ruleFactory->create();
                $rule->getConditions()->loadArray($conditions);
            }
        }

        $lastEntityId = 0;
        $selected = 0;
        $processed = 0;
        $exported = 0;
        $skipped = 0;

        // Get total catalog count for selected
        $totalCollection = $this->getProductCollection($profile, $rule);
        $selected = $totalCollection->getSize();
        if ($job) {
            $job->setSelectedCount($selected);
            $job->setTotalProducts($selected);
        }

        while (true) {
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
                    $xmlItem = "  <item>\n";
                    foreach ($mapping as $map) {
                        $googleTag = $map['google_attribute'];
                        if (!preg_match('/^(?:[A-Za-z_][A-Za-z0-9_.-]*:)?[A-Za-z_][A-Za-z0-9_.-]*$/', $googleTag)) {
                            throw new \InvalidArgumentException('Invalid XML output field name.');
                        }
                        $value = $this->resolveFeedValue($map, $feedProduct, $profile);
                        $value = $this->applyModifier($value, $map['modifier'] ?? '', $feedProduct);

                        if ($value !== null && $value !== '') {
                            $safeValue = str_replace(']]>', ']]]]><![CDATA[>', (string)$value);
                            $xmlItem .= "    <{$googleTag}><![CDATA[{$safeValue}]]></{$googleTag}>\n";
                        }
                    }
                    $xmlItem .= "  </item>\n";
                    $stream->write($xmlItem);
                    $exported++;
                }
            }

            if ($job) {
                $job->setProcessedProducts($processed);
                $job->setExportedCount($exported);
                $job->setSkippedCount($skipped);
                $this->jobResource->save($job);
            }

            $collection->clear();
        }

        // Write XML Footer
        $xmlFooter = '</channel>' . "\n";
        $xmlFooter .= '</rss>';
        $stream->write($xmlFooter);

            return true;
        } finally {
            if ($stream) {
                $stream->unlock();
                $stream->close();
            }
        }
    }

    /**
     * Resolve values that Google expects in a normalized form.
     *
     * @param array $map
     * @param \Magento\Catalog\Model\Product $product
     * @param FeedProfileInterface $profile
     * @return mixed
     */
    protected function resolveFeedValue(array $map, $product, FeedProfileInterface $profile)
    {
        $googleAttribute = $map['google_attribute'] ?? '';
        $magentoAttribute = $map['magento_attribute'] ?? '';

        switch ($googleAttribute) {
            case 'g:id':
            case 'id':
                if ($product->getData('_feed_parent_sku')) {
                    return $product->getData('_feed_parent_sku') . '-' . $product->getSku();
                }
                return $product->getSku();

            case 'g:item_group_id':
            case 'item_group_id':
                return $product->getData('_feed_item_group_id');

            case 'g:link':
            case 'link':
                return $this->utmBuilder->buildUrl($product->getProductUrl(), $profile, $product);

            case 'g:image_link':
            case 'image_link':
                $image = (string)$product->getData($magentoAttribute ?: 'image');
                if ($image === '' || $image === 'no_selection') {
                    return '';
                }
                return $this->storeManager->getStore($profile->getStoreId())->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'catalog/product' . $image;

            case 'g:price':
            case 'price':
                $price = (float)$product->getFinalPrice();
                return number_format($price, 2, '.', '') . ' ' . $profile->getCurrency();

            case 'g:availability':
            case 'availability':
                return $product->isSalable() ? 'in_stock' : 'out_of_stock';

            case 'g:condition':
            case 'condition':
                return 'new';

            case 'g:brand':
            case 'brand':
                $label = $product->getAttributeText($magentoAttribute ?: 'manufacturer');
                return is_array($label) ? implode(', ', $label) : $label;
        }

        if ($magentoAttribute === 'url') {
            return $this->utmBuilder->buildUrl($product->getProductUrl(), $profile, $product);
        }

        return $product->getData($magentoAttribute);
    }

    /**
     * Apply modifiers
     *
     * @param string $value
     * @param string $modifierCode
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    protected function applyModifier($value, $modifierCode, $product)
    {
        return $this->modifierPool->apply($value, $modifierCode, $product);
    }
}
