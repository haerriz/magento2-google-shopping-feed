<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;

class ProductProvider implements ProductProviderInterface
{
    private $collectionFactory;
    private $stockStatusResource;
    private $configReader;
    private $categoryIdResolver;

    public function __construct(
        CollectionFactory $collectionFactory,
        StockStatusResource $stockStatusResource,
        ProfileConfigReader $configReader,
        CategoryIdResolver $categoryIdResolver
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->stockStatusResource = $stockStatusResource;
        $this->configReader = $configReader;
        $this->categoryIdResolver = $categoryIdResolver;
    }

    public function getCollection(
        FeedProfileInterface $profile,
        $rule = null,
        $afterEntityId = 0,
        $pageSize = 500
    ) {
        $collection = $this->collectionFactory->create();
        $collection->addStoreFilter((int)$profile->getStoreId());
        $collection->addAttributeToSelect($this->getRequiredAttributes($profile));

        if (!$this->configReader->getBoolean($profile, 'include_disabled')) {
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        }

        $visibility = $this->configReader->getIntList($profile, 'visibility_values');
        if ($visibility) {
            $collection->addAttributeToFilter('visibility', ['in' => $visibility]);
        }

        $includeCategories = $this->categoryIdResolver->resolve(
            $this->configReader->getIntList($profile, 'include_category_ids'),
            $this->configReader->getBoolean($profile, 'include_category_descendants', true)
        );
        if ($includeCategories) {
            $collection->addCategoriesFilter(['in' => $includeCategories]);
        }

        $excludeCategories = $this->categoryIdResolver->resolve(
            $this->configReader->getIntList($profile, 'exclude_category_ids'),
            $this->configReader->getBoolean($profile, 'include_category_descendants', true)
        );
        if ($excludeCategories) {
            $collection->addCategoriesFilter(['nin' => $excludeCategories]);
        }

        $stockPolicy = $this->configReader->get($profile, 'stock_policy', 'in_stock');
        $this->stockStatusResource->addStockDataToCollection($collection, $stockPolicy === 'in_stock');
        if ($stockPolicy === 'out_of_stock') {
            $collection->getSelect()->where('stock_status_index.stock_status = ?', 0);
        }

        $excludedTypes = [];
        if (!$this->configReader->getBoolean($profile, 'include_virtual')) {
            $excludedTypes[] = Type::TYPE_VIRTUAL;
        }
        if (!$this->configReader->getBoolean($profile, 'include_downloadable')) {
            $excludedTypes[] = 'downloadable';
        }
        if ($excludedTypes) {
            $collection->addFieldToFilter('type_id', ['nin' => $excludedTypes]);
        }

        if ($rule) {
            $rule->getConditions()->collectValidatedAttributes($collection);
        }

        if ($afterEntityId > 0) {
            $collection->addFieldToFilter('entity_id', ['gt' => $afterEntityId]);
        }
        $collection->setOrder('entity_id', 'ASC');
        $collection->setPageSize(max(1, min(5000, (int)$pageSize)));

        return $collection;
    }

    private function getRequiredAttributes(FeedProfileInterface $profile)
    {
        $attributes = ['sku', 'name', 'price', 'special_price', 'special_from_date', 'special_to_date', 'image'];
        $mapping = json_decode((string)$profile->getAttributesMappingSerialized(), true);
        foreach (is_array($mapping) ? $mapping : [] as $field) {
            $code = $field['magento_attribute'] ?? '';
            if (preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
                $attributes[] = $code;
            }
        }

        return array_values(array_unique($attributes));
    }
}
