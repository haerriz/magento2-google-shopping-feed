<?php
namespace Haerriz\GoogleShoppingFeed\Api;

interface DeliveryAdapterInterface
{
    public function deliver(\Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface $profile, string $filePath): bool;
}
