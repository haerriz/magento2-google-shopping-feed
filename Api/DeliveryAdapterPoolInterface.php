<?php
namespace Haerriz\GoogleShoppingFeed\Api;

interface DeliveryAdapterPoolInterface
{
    public function getAdapter(string $type): DeliveryAdapterInterface;
}
