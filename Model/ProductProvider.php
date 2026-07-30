<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductProvider implements ProductProviderInterface
{
    private $productCollectionFactory;

    public function __construct(CollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function getCollection(
        FeedProfileInterface $profile,
        $rule = null,
        $afterEntityId = 0,
        $pageSize = 500
    ): Collection {
        return $this->getProducts($profile);
    }

    public function getProducts(FeedProfileInterface $profile): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['sku', 'name', 'price', 'quantity_and_stock_status', 'image', 'description', 'status', 'visibility']);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->setStoreId((int)$profile->getStoreId());
        
        $excludedCatIds = $profile->getExcludeCategoryIds();
        if ($excludedCatIds) {
            $catIds = array_filter(explode(',', (string)$excludedCatIds));
            if (!empty($catIds)) {
                $collection->addCategoriesFilter(['eq' => $catIds]);
            }
        }
        return $collection;
    }
}
