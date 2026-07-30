<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\ProductValueResolverInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Catalog\Model\Product;

class ProductValueResolver implements ProductValueResolverInterface
{
    public function resolve(array $mapping, Product $product, FeedProfileInterface $profile)
    {
        $sourceType = $mapping['source_type'] ?? 'attribute';
        if ($sourceType === 'static') {
            return $mapping['static_value'] ?? '';
        }
        $attributeCode = $mapping['magento_attribute'] ?? $mapping['attribute'] ?? '';
        if (!$attributeCode) {
            return '';
        }
        if ($attributeCode === 'quantity') {
            $stockItem = $product->getExtensionAttributes() ? $product->getExtensionAttributes()->getStockItem() : null;
            return $stockItem ? (int)$stockItem->getQty() : 100;
        }
        return $product->getData($attributeCode) ?? '';
    }
}
