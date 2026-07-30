<?php
namespace Haerriz\GoogleShoppingFeed\Api;

interface CategoryMappingRepositoryInterface
{
    public function getByCategoryId(int $categoryId): ?string;
}
