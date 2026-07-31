<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\ProductValueResolverInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductValueResolver implements ProductValueResolverInterface
{
    private $imageHelper;
    private $storeManager;

    public function __construct(
        ImageHelper $imageHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
    }

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

        switch ($attributeCode) {
            case 'product_url':
                return $product->setStoreId((int)$profile->getStoreId())->getProductUrl();

            case 'image_url':
                try {
                    return $this->imageHelper
                        ->init($product, 'product_page_image_large')
                        ->setImageFile($product->getImage())
                        ->getUrl();
                } catch (\Exception $e) {
                    return '';
                }

            case 'quantity_and_stock_status':
                try {
                    $stockItem = $product->getExtensionAttributes()
                        ? $product->getExtensionAttributes()->getStockItem()
                        : null;
                    if ($stockItem && $stockItem->getIsInStock()) {
                        return 'in stock';
                    }
                } catch (\Exception $e) {}
                return 'out of stock';

            case 'quantity':
                try {
                    $stockItem = $product->getExtensionAttributes()
                        ? $product->getExtensionAttributes()->getStockItem()
                        : null;
                    return $stockItem ? max(0, (int)$stockItem->getQty()) : 0;
                } catch (\Exception $e) {
                    return 0;
                }

            case 'price':
                // Use special price if valid and active
                $specialPrice = (float)$product->getSpecialPrice();
                if ($specialPrice > 0) {
                    $from = $product->getSpecialFromDate();
                    $to   = $product->getSpecialToDate();
                    $now  = date('Y-m-d');
                    $valid = (!$from || $now >= substr($from, 0, 10))
                          && (!$to   || $now <= substr($to, 0, 10));
                    if ($valid) {
                        return number_format($specialPrice, 2, '.', '') . ' INR';
                    }
                }
                return number_format((float)$product->getPrice(), 2, '.', '') . ' INR';

            default:
                $value = $product->getData($attributeCode);
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                return (string)($value ?? '');
        }
    }
}
