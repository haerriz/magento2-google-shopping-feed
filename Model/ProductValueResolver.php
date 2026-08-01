<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\OfferIdentityResolverInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductValueResolverInterface;
use Haerriz\GoogleShoppingFeed\Model\Url\UtmBuilder;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductValueResolver implements ProductValueResolverInterface
{
    private ImageHelper $imageHelper;
    private StoreManagerInterface $storeManager;
    private OfferIdentityResolverInterface $offerIdentityResolver;
    private UtmBuilder $utmBuilder;
    private CategoryIdResolver $categoryIdResolver;
    private LoggerInterface $logger;

    public function __construct(
        ImageHelper $imageHelper,
        StoreManagerInterface $storeManager,
        OfferIdentityResolverInterface $offerIdentityResolver,
        UtmBuilder $utmBuilder,
        CategoryIdResolver $categoryIdResolver,
        LoggerInterface $logger
    ) {
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->offerIdentityResolver = $offerIdentityResolver;
        $this->utmBuilder = $utmBuilder;
        $this->categoryIdResolver = $categoryIdResolver;
        $this->logger = $logger;
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
            case 'sku':
            case 'offer_id':
                try {
                    return $this->offerIdentityResolver->resolve($product);
                } catch (\Exception $e) {
                    $this->logger->debug("OfferIdentityResolver failed for [{$product->getId()}]: " . $e->getMessage());
                    return (string)$product->getSku();
                }

            case 'product_url':
                try {
                    $url = $product->setStoreId((int)$profile->getStoreId())->getProductUrl();
                    return $this->utmBuilder->build($url, $profile);
                } catch (\Exception $e) {
                    $this->logger->debug("UtmBuilder failed for [{$product->getSku()}]: " . $e->getMessage());
                    return $product->getProductUrl();
                }

            case 'image_url':
                try {
                    return $this->imageHelper
                        ->init($product, 'product_page_image_large')
                        ->setImageFile($product->getImage())
                        ->getUrl();
                } catch (\Exception $e) {
                    $this->logger->debug("ImageHelper failed for [{$product->getSku()}]: " . $e->getMessage());
                    return '';
                }

            case 'google_product_category':
                try {
                    return $this->categoryIdResolver->resolve($product);
                } catch (\Exception $e) {
                    $this->logger->debug("CategoryIdResolver failed for [{$product->getSku()}]: " . $e->getMessage());
                    return '';
                }

            case 'item_group_id':
                return (string)($product->getData('item_group_id') ?: $product->getData('parent_sku') ?: '');

            case 'quantity_and_stock_status':
            case 'availability':
                try {
                    $stockItem = $product->getExtensionAttributes()
                        ? $product->getExtensionAttributes()->getStockItem()
                        : null;
                    if ($stockItem && $stockItem->getIsInStock()) {
                        return 'in stock';
                    }
                } catch (\Exception $e) {
                    // fall through
                }
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
                $currency = $this->resolveCurrency($product, $profile);
                $specialPrice = (float)$product->getSpecialPrice();
                if ($specialPrice > 0) {
                    $from = $product->getSpecialFromDate();
                    $to = $product->getSpecialToDate();
                    $now = date('Y-m-d');
                    $valid = (!$from || $now >= substr((string)$from, 0, 10))
                        && (!$to || $now <= substr((string)$to, 0, 10));
                    if ($valid) {
                        return number_format($specialPrice, 2, '.', '') . ' ' . $currency;
                    }
                }
                return number_format((float)$product->getFinalPrice(), 2, '.', '') . ' ' . $currency;

            case 'currency':
                return $this->resolveCurrency($product, $profile);

            default:
                $value = $product->getData($attributeCode);
                if ($value === null) {
                    $getter = 'get' . str_replace('_', '', ucwords($attributeCode, '_'));
                    if (method_exists($product, $getter)) {
                        $value = $product->$getter();
                    }
                }
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                return (string)($value ?? '');
        }
    }

    private function resolveCurrency(Product $product, FeedProfileInterface $profile): string
    {
        $currency = trim((string)$profile->getCurrency());
        if ($currency !== '') {
            return strtoupper($currency);
        }

        try {
            $storeId = (int)($profile->getStoreId() ?: $product->getStoreId());
            return (string)$this->storeManager->getStore($storeId)->getCurrentCurrencyCode();
        } catch (\Exception $e) {
            $this->logger->debug('Currency resolve failed: ' . $e->getMessage());
            return 'USD';
        }
    }
}
