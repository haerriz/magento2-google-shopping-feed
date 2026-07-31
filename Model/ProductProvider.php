<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Helper\Stock as StockHelper;

class ProductProvider implements ProductProviderInterface
{
    private $productCollectionFactory;
    private $stockHelper;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        StockHelper $stockHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockHelper = $stockHelper;
    }

    /**
     * Returns a paginated, deterministic, keyset-safe product collection.
     * CRITICAL: $afterEntityId and $pageSize MUST be honoured to prevent infinite loops.
     */
    public function getCollection(
        FeedProfileInterface $profile,
        $rule = null,
        $afterEntityId = 0,
        $pageSize = 500
    ): Collection {
        $collection = $this->productCollectionFactory->create();

        // Core attributes needed for all channels
        $collection->addAttributeToSelect([
            'sku', 'name', 'price', 'special_price', 'special_from_date', 'special_to_date',
            'image', 'small_image', 'thumbnail', 'description', 'short_description',
            'status', 'visibility', 'tax_class_id', 'weight', 'manufacturer', 'color', 'size',
            'meta_title', 'meta_description', 'meta_keyword', 'url_key',
            'quantity_and_stock_status'
        ]);

        // Only enabled products
        $collection->addAttributeToFilter(
            'status',
            \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        );

        // Only individually visible products (not variants)
        $collection->addAttributeToFilter('visibility', [
            'in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
            ]
        ]);

        // Store scope
        $collection->setStoreId((int)$profile->getStoreId());

        // Exclude categories if configured
        $excludedCatIds = $profile->getExcludeCategoryIds();
        if ($excludedCatIds) {
            $catIds = array_filter(array_map('intval', explode(',', (string)$excludedCatIds)));
            if (!empty($catIds)) {
                $collection->addCategoriesFilter(['nin' => $catIds]);
            }
        }

        // Add stock info
        $this->stockHelper->addInStockFilterToCollection($collection);

        // KEYSET PAGINATION — critical for preventing infinite loop
        if ($afterEntityId > 0) {
            $collection->addFieldToFilter('entity_id', ['gt' => $afterEntityId]);
        }

        // Always sort ascending by entity_id for stable keyset cursor
        $collection->addOrder('entity_id', Collection::SORT_ORDER_ASC);
        $collection->setPageSize((int)$pageSize);
        $collection->setCurPage(1);

        return $collection;
    }
}
