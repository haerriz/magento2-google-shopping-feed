<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface FeedJobItemInterface
{
    public function getId();
    public function getJobId(): int;
    public function getProductId(): int;
    public function getStatus(): string;
}
