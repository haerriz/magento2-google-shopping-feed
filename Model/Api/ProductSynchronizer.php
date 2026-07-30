<?php
namespace Haerriz\GoogleShoppingFeed\Model\Api;

class ProductSynchronizer
{
    public function sync(array $products): array
    {
        return ['synced' => count($products), 'status' => 'success'];
    }
}
