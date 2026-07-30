<?php
namespace Haerriz\GoogleShoppingFeed\Model\Product\Type;

class Pool
{
    public function getStrategy(string $typeId): TypeStrategyInterface
    {
        return new Simple();
    }
}
