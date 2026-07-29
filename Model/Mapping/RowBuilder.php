<?php
namespace Haerriz\GoogleShoppingFeed\Model\Mapping;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ModifierPipelineInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductValueResolverInterface;
use Haerriz\GoogleShoppingFeed\Model\ProfileConfigReader;
use Magento\Catalog\Model\Product;

class RowBuilder
{
    private $valueResolver;
    private $modifierPipeline;
    private $configReader;

    public function __construct(
        ProductValueResolverInterface $valueResolver,
        ModifierPipelineInterface $modifierPipeline,
        ProfileConfigReader $configReader
    ) {
        $this->valueResolver = $valueResolver;
        $this->modifierPipeline = $modifierPipeline;
        $this->configReader = $configReader;
    }

    public function getMappings(FeedProfileInterface $profile)
    {
        $mappings = json_decode((string)$profile->getAttributesMappingSerialized(), true);
        if (!is_array($mappings)) {
            return [];
        }
        $chains = json_decode((string)$this->configReader->get($profile, 'modifier_chains_serialized', '[]'), true);
        $chains = is_array($chains) ? $chains : [];
        foreach ($mappings as $index => &$mapping) {
            if (!isset($mapping['modifiers'])) {
                $field = (string)($mapping['google_attribute'] ?? '');
                $mapping['modifiers'] = $chains[$field] ?? $chains[$index] ?? [];
                if (!$mapping['modifiers'] && !empty($mapping['modifier'])) {
                    $mapping['modifiers'] = [['code' => 'legacy', 'value' => $mapping['modifier']]];
                }
            }
        }
        unset($mapping);
        return $mappings;
    }

    public function build(Product $product, FeedProfileInterface $profile)
    {
        $row = [];
        foreach ($this->getMappings($profile) as $mapping) {
            $field = (string)($mapping['google_attribute'] ?? $mapping['field'] ?? '');
            if ($field === '') {
                throw new \InvalidArgumentException('Every mapping requires an output field.');
            }
            $value = $this->valueResolver->resolve($mapping, $product, $profile);
            $row[$field] = $this->modifierPipeline->apply(
                $value,
                (array)($mapping['modifiers'] ?? []),
                $product,
                $profile
            );
        }
        return $row;
    }

    public function validate(FeedProfileInterface $profile)
    {
        $errors = [];
        $fields = [];
        foreach ($this->getMappings($profile) as $mapping) {
            $field = (string)($mapping['google_attribute'] ?? $mapping['field'] ?? '');
            if ($field === '' || !preg_match('/^(?:g:)?[A-Za-z_][A-Za-z0-9_.-]*$/', $field)) {
                $errors[] = 'Invalid output field name: ' . $field;
            } elseif (isset($fields[$field])) {
                $errors[] = 'Duplicate output field: ' . $field;
            }
            $fields[$field] = true;
            try {
                $this->modifierPipeline->validate((array)($mapping['modifiers'] ?? []));
            } catch (\InvalidArgumentException $exception) {
                $errors[] = $field . ': ' . $exception->getMessage();
            }
        }
        return $errors;
    }
}
