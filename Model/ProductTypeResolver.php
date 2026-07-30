<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Magento\Catalog\Model\Product;

class ProductTypeResolver
{
    public function resolveType(Product $product): string
    {
        return (string)$product->getTypeId();
    }

    public function isExportable(Product $product): bool
    {
        return (int)$product->getStatus() === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }
}
