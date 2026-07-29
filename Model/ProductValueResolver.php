<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductValueResolverInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductValueResolver implements ProductValueResolverInterface
{
    private $storeManager;
    private $catalogHelper;
    private $priceCurrency;
    private $utmBuilder;

    public function __construct(
        StoreManagerInterface $storeManager,
        CatalogHelper $catalogHelper,
        PriceCurrencyInterface $priceCurrency,
        Url\UtmBuilder $utmBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->catalogHelper = $catalogHelper;
        $this->priceCurrency = $priceCurrency;
        $this->utmBuilder = $utmBuilder;
    }

    public function resolve(array $mapping, Product $product, FeedProfileInterface $profile)
    {
        $field = strtolower((string)($mapping['google_attribute'] ?? $mapping['field'] ?? ''));
        $attribute = (string)($mapping['magento_attribute'] ?? $mapping['source'] ?? '');
        $sourceType = (string)($mapping['source_type'] ?? 'attribute');

        if ($sourceType === 'static') {
            return $mapping['static_value'] ?? '';
        }
        $value = $this->resolveSpecial($field, $attribute, $product, $profile, $mapping);
        if (($value === null || $value === '') && array_key_exists('default_value', $mapping)) {
            $value = $mapping['default_value'];
        }
        if (($value === null || $value === '') && !empty($mapping['fallback_attributes'])) {
            foreach ((array)$mapping['fallback_attributes'] as $fallback) {
                $value = $this->attributeValue($product, (string)$fallback);
                if ($value !== null && $value !== '') {
                    break;
                }
            }
        }
        if (($value === null || $value === '') && $product->getData('_feed_parent_product') instanceof Product) {
            $value = $this->resolveSpecial(
                $field,
                $attribute,
                $product->getData('_feed_parent_product'),
                $profile,
                $mapping
            );
        }
        return $value;
    }

    private function resolveSpecial(
        $field,
        $attribute,
        Product $product,
        FeedProfileInterface $profile,
        array $mapping
    ) {
        switch ($field) {
            case 'id':
            case 'g:id':
                return $product->getData('_feed_parent_sku')
                    ? $product->getData('_feed_parent_sku') . '-' . $product->getSku()
                    : $product->getSku();
            case 'item_group_id':
            case 'g:item_group_id':
                return $product->getData('_feed_item_group_id');
            case 'title':
            case 'g:title':
                return $this->attributeValue($product, $attribute ?: 'name');
            case 'description':
            case 'g:description':
                return $this->attributeValue($product, $attribute ?: 'description');
            case 'link':
            case 'g:link':
                return $this->utmBuilder->buildUrl($product->getProductUrl(), $profile, $product);
            case 'image_link':
            case 'g:image_link':
                return $this->mediaUrl($product, $attribute ?: 'image', $profile);
            case 'price':
            case 'g:price':
                return $this->price($product, $profile, false, !empty($mapping['include_tax']));
            case 'sale_price':
            case 'g:sale_price':
                return $this->price($product, $profile, true, !empty($mapping['include_tax']));
            case 'sale_price_effective_date':
            case 'g:sale_price_effective_date':
                $from = $product->getSpecialFromDate();
                $to = $product->getSpecialToDate();
                return $from ? gmdate('c', strtotime($from)) . '/' . ($to ? gmdate('c', strtotime($to)) : '') : '';
            case 'availability':
            case 'g:availability':
                return ((int)$product->getData('is_salable') === 1 || $product->isSalable())
                    ? 'in_stock'
                    : 'out_of_stock';
            case 'condition':
            case 'g:condition':
                return $attribute ? $this->attributeValue($product, $attribute) : 'new';
            case 'brand':
            case 'g:brand':
                return $this->attributeValue($product, $attribute ?: 'manufacturer');
            case 'identifier_exists':
            case 'g:identifier_exists':
                return ($this->attributeValue($product, 'gtin') || $this->attributeValue($product, 'mpn'))
                    ? 'yes'
                    : 'no';
            case 'product_type':
            case 'g:product_type':
                return $product->getTypeId();
        }
        return $this->attributeValue($product, $attribute);
    }

    private function attributeValue(Product $product, $attribute)
    {
        if ($attribute === '') {
            return null;
        }
        $text = $product->getAttributeText($attribute);
        if ($text !== false && $text !== null) {
            return is_array($text) ? implode(', ', $text) : $text;
        }
        return $product->getData($attribute);
    }

    private function mediaUrl(Product $product, $attribute, FeedProfileInterface $profile)
    {
        $path = (string)$product->getData($attribute);
        if ($path === '' || $path === 'no_selection') {
            return '';
        }
        return rtrim(
            $this->storeManager->getStore($profile->getStoreId())->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
            '/'
        ) . '/catalog/product/' . ltrim($path, '/');
    }

    private function price(Product $product, FeedProfileInterface $profile, $sale, $includeTax)
    {
        $amount = $sale ? (float)$product->getFinalPrice() : (float)$product->getPrice();
        if ($sale && $amount >= (float)$product->getPrice()) {
            return '';
        }
        $store = $this->storeManager->getStore($profile->getStoreId());
        $amount = $this->catalogHelper->getTaxPrice(
            $product,
            $amount,
            (bool)$includeTax,
            null,
            null,
            null,
            $store
        );
        $amount = $this->priceCurrency->convert($amount, $store, $profile->getCurrency());
        return number_format((float)$amount, 2, '.', '') . ' ' . $profile->getCurrency();
    }
}
