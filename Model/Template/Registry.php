<?php
namespace Haerriz\GoogleShoppingFeed\Model\Template;

use Haerriz\GoogleShoppingFeed\Api\FeedTemplateRegistryInterface;
use Haerriz\GoogleShoppingFeed\Api\FeedTemplateInterface;

class Registry implements FeedTemplateRegistryInterface
{
    private array $templates = [];

    public function __construct()
    {
        $this->templates['google_shopping_v1'] = new Google\ShoppingV1();
        $this->templates['meta_catalog_v1'] = new Meta\CatalogV1();
    }

    public function getTemplate(string $code): FeedTemplateInterface
    {
        return $this->templates[$code] ?? $this->templates['google_shopping_v1'];
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }
}
