<?php
namespace Haerriz\GoogleShoppingFeed\Block\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;

class StructuredData extends Template
{
    private $registry;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getJsonLd()
    {
        $product = $this->getProduct();
        if (!$product) {
            return '';
        }

        $store = $this->_storeManager->getStore();
        $currency = $store->getCurrentCurrencyCode();

        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => (string)$product->getName(),
            'sku' => (string)$product->getSku(),
            'image' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
            'description' => strip_tags((string)$product->getDescription()),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $currency,
                'price' => number_format((float)$product->getFinalPrice(), 2, '.', ''),
                'availability' => $product->isAvailable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $product->getProductUrl()
            ]
        ];

        // Security Encoding: JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP to prevent breaking script context
        return json_encode($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
