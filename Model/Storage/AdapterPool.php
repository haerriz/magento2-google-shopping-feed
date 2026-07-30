<?php
namespace Haerriz\GoogleShoppingFeed\Model\Storage;

use Haerriz\GoogleShoppingFeed\Model\Delivery\DeliveryPool;

class AdapterPool
{
    private $deliveryPool;

    public function __construct(DeliveryPool $deliveryPool)
    {
        $this->deliveryPool = $deliveryPool;
    }

    public function get(string $code)
    {
        return $this->deliveryPool->get($code);
    }
}
