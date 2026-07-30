<?php
namespace Haerriz\GoogleShoppingFeed\Model\Template;

class PresetRegistry
{
    public function getPresets(): array
    {
        return [
            'google' => [
                'name' => 'Google Shopping',
                'format' => 'xml',
                'mapping' => [
                    ['google_attribute' => 'g:id', 'magento_attribute' => 'sku'],
                    ['google_attribute' => 'g:title', 'magento_attribute' => 'name'],
                    ['google_attribute' => 'g:price', 'magento_attribute' => 'price'],
                    ['google_attribute' => 'g:availability', 'magento_attribute' => 'quantity']
                ]
            ],
            'meta' => [
                'name' => 'Meta Facebook Catalog',
                'format' => 'csv',
                'mapping' => [
                    ['google_attribute' => 'id', 'magento_attribute' => 'sku'],
                    ['google_attribute' => 'title', 'magento_attribute' => 'name'],
                    ['google_attribute' => 'price', 'magento_attribute' => 'price']
                ]
            ]
        ];
    }
}
