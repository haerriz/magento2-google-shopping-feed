<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Haerriz\GoogleShoppingFeed\Model\Modifier\Pool as ModifierPool;

use Haerriz\GoogleShoppingFeed\Model\Storage\AdapterPool;

use Haerriz\GoogleShoppingFeed\Model\RuleFactory;

class FeedGenerator
{
    const BATCH_SIZE = 500;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

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
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ModifierPool $modifierPool
     * @param AdapterPool $adapterPool
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        ModifierPool $modifierPool,
        AdapterPool $adapterPool,
        RuleFactory $ruleFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->modifierPool = $modifierPool;
        $this->adapterPool = $adapterPool;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Generate feed
     *
     * @param FeedProfileInterface $profile
     * @return bool
     */
    public function generate(FeedProfileInterface $profile)
    {
        $storeId = $profile->getStoreId();
        $this->storeManager->setCurrentStore($storeId);

        $type = $profile->getFeedType();
        $filename = $profile->getFilename();

        if ($type === 'csv') {
            $result = $this->generateCsv($profile, $filename);
        } else {
            $result = $this->generateXml($profile, $filename);
        }

        if ($result) {
            $localPath = 'google_feed/' . $filename;
            $deliveryType = $profile->getDeliveryType() ?: 'local';
            $adapter = $this->adapterPool->get($deliveryType);
            return $adapter->upload($profile, $localPath);
        }

        return false;
    }

    /**
     * Get base product collection with filters
     *
     * @param int $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection($storeId, $rule = null)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]);
        
        if ($rule) {
            $rule->getConditions()->collectValidatedAttributes($collection);
        }
        
        return $collection;
    }

    /**
     * Generate CSV feed with batching and streaming
     *
     * @param FeedProfileInterface $profile
     * @param string $filename
     * @return bool
     */
    protected function generateCsv(FeedProfileInterface $profile, $filename)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream = $directory->openFile('google_feed/' . $filename, 'w+');
        $stream->lock();

        $mapping = json_decode($profile->getAttributesMappingSerialized(), true) ?? [];
        
        // Write CSV Headers
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
        $page = 1;
        $storeId = $profile->getStoreId();
        
        while (true) {
            $collection = $this->getProductCollection($storeId, $rule);
            $collection->setPage($page, self::BATCH_SIZE);
            
            if ($collection->count() === 0) {
                break;
            }

            foreach ($collection as $product) {
                if ($rule && !$rule->getConditions()->validate($product)) {
                    continue;
                }

                $row = [];
                foreach ($mapping as $map) {
                    $value = $product->getData($map['magento_attribute']);
                    $value = $this->applyModifier($value, $map['modifier'] ?? '', $product);
                    $row[] = $value;
                }
                $stream->writeCsv($row);
            }

            $collection->clear();
            $page++;
        }

        $stream->unlock();
        $stream->close();
        return true;
    }

    /**
     * Generate XML feed with batching and streaming
     *
     * @param FeedProfileInterface $profile
     * @param string $filename
     * @return bool
     */
    protected function generateXml(FeedProfileInterface $profile, $filename)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream = $directory->openFile('google_feed/' . $filename, 'w+');
        $stream->lock();

        // Write XML Header
        $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlHeader .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xmlHeader .= '<channel>' . "\n";
        $xmlHeader .= '<title><![CDATA[' . $profile->getName() . ']]></title>' . "\n";
        $xmlHeader .= '<link><![CDATA[' . $this->storeManager->getStore()->getBaseUrl() . ']]></link>' . "\n";
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

        $page = 1;
        $storeId = $profile->getStoreId();

        while (true) {
            $collection = $this->getProductCollection($storeId, $rule);
            $collection->setPage($page, self::BATCH_SIZE);

            if ($collection->count() === 0) {
                break;
            }

            foreach ($collection as $product) {
                if ($rule && !$rule->getConditions()->validate($product)) {
                    continue;
                }

                $xmlItem = "  <item>\n";
                foreach ($mapping as $map) {
                    $googleTag = $map['google_attribute'];
                    $value = $product->getData($map['magento_attribute']);
                    $value = $this->applyModifier($value, $map['modifier'] ?? '', $product);

                    if ($value !== null && $value !== '') {
                        $xmlItem .= "    <{$googleTag}><![CDATA[{$value}]]></{$googleTag}>\n";
                    }
                }
                $xmlItem .= "  </item>\n";
                $stream->write($xmlItem);
            }

            $collection->clear();
            $page++;
        }

        // Write XML Footer
        $xmlFooter = '</channel>' . "\n";
        $xmlFooter .= '</rss>';
        $stream->write($xmlFooter);

        $stream->unlock();
        $stream->close();
        return true;
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
