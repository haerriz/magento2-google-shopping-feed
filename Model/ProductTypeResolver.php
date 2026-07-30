<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Magento\Catalog\Model\Product;

class ProductTypeResolver implements ProductTypeResolverInterface
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
