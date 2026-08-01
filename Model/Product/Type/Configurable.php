<?php
namespace Haerriz\GoogleShoppingFeed\Model\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Configurable implements TypeStrategyInterface
{
    public function resolveProducts(Product $product): array
    {
        $typeInstance = $product->getTypeInstance();
        if (!method_exists($typeInstance, 'getUsedProducts')) {
            return [$product];
        }

        $children = [];
        foreach ($typeInstance->getUsedProducts($product) as $child) {
            if (!$child instanceof Product) {
                continue;
            }
            if ((int)$child->getStatus() !== Status::STATUS_ENABLED) {
                continue;
            }
            $child->setData('parent_sku', $product->getSku());
            $child->setData('item_group_id', $product->getSku());
            $children[] = $child;
        }

        // Fall back to parent if no enabled children are available.
        return $children ?: [$product];
    }

    public function prepareData(Product $product): array
    {
        $skus = array_map(
            static fn(Product $child) => (string)$child->getSku(),
            $this->resolveProducts($product)
        );

        return [
            'sku' => (string)$product->getSku(),
            'type' => 'configurable',
            'variant_skus' => $skus,
            'item_group_id' => (string)$product->getSku(),
        ];
    }
}
