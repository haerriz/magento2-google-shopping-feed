<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Magento\Framework\App\ResourceConnection;

class ProductTypeResolver implements ProductTypeResolverInterface
{
    private const GROUPED_LINK_TYPE_ID = 3;

    private $resourceConnection;
    private $collectionFactory;
    private $stockStatusResource;
    private $configReader;
    private $childrenByParent = [];

    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionFactory $collectionFactory,
        StockStatusResource $stockStatusResource,
        ProfileConfigReader $configReader
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->collectionFactory = $collectionFactory;
        $this->stockStatusResource = $stockStatusResource;
        $this->configReader = $configReader;
    }

    public function prepare($collection, FeedProfileInterface $profile)
    {
        $this->childrenByParent = [];
        $parentsByType = [
            'configurable' => [],
            Type::TYPE_BUNDLE => [],
            'grouped' => [],
        ];
        foreach ($collection as $product) {
            if (isset($parentsByType[$product->getTypeId()])) {
                $parentsByType[$product->getTypeId()][] = (int)$product->getId();
            }
        }

        $relations = [];
        $relations += $this->fetchRelations(
            'catalog_product_super_link',
            'parent_id',
            'product_id',
            $parentsByType['configurable']
        );
        $relations += $this->fetchRelations(
            'catalog_product_bundle_selection',
            'parent_product_id',
            'product_id',
            $parentsByType[Type::TYPE_BUNDLE]
        );
        $relations += $this->fetchRelations(
            'catalog_product_link',
            'product_id',
            'linked_product_id',
            $parentsByType['grouped'],
            'link_type_id = ' . self::GROUPED_LINK_TYPE_ID
        );
        if (!$relations) {
            return;
        }

        $childIds = [];
        foreach ($relations as $parentId => $ids) {
            $childIds = array_merge($childIds, $ids);
        }
        $children = $this->collectionFactory->create();
        $children->addStoreFilter((int)$profile->getStoreId());
        $children->addIdFilter(array_values(array_unique($childIds)));
        $children->addAttributeToSelect($this->getRequiredAttributes($profile));
        $this->stockStatusResource->addStockDataToCollection($children, false);

        $childrenById = [];
        foreach ($children as $child) {
            $childrenById[(int)$child->getId()] = $child;
        }
        foreach ($relations as $parentId => $ids) {
            foreach ($ids as $childId) {
                if (isset($childrenById[$childId])) {
                    $this->childrenByParent[$parentId][] = $childrenById[$childId];
                }
            }
        }
    }

    public function resolve($product, FeedProfileInterface $profile)
    {
        $typeId = $product->getTypeId();
        if ($typeId === Type::TYPE_VIRTUAL && !$this->configReader->getBoolean($profile, 'include_virtual')) {
            return [];
        }
        if ($typeId === 'downloadable' && !$this->configReader->getBoolean($profile, 'include_downloadable')) {
            return [];
        }

        $strategyField = [
            'configurable' => 'configurable_strategy',
            Type::TYPE_BUNDLE => 'bundle_strategy',
            'grouped' => 'grouped_strategy',
        ][$typeId] ?? null;
        if ($strategyField === null) {
            return [$product];
        }

        $strategy = (string)$this->configReader->get($profile, $strategyField, 'parent');
        $children = $this->childrenByParent[(int)$product->getId()] ?? [];
        if ($typeId === 'configurable') {
            $children = array_values(array_filter($children, function (Product $child) {
                return (int)$child->getData('is_salable') === 1;
            }));
        }
        foreach ($children as $child) {
            $child->setData('_feed_parent_id', (int)$product->getId());
            $child->setData('_feed_parent_sku', (string)$product->getSku());
            $child->setData('_feed_item_group_id', (string)$product->getSku());
        }

        if (in_array($strategy, ['variants', 'children'], true)) {
            return $children;
        }
        if ($strategy === 'both') {
            return array_merge([$product], $children);
        }
        return [$product];
    }

    private function fetchRelations($table, $parentColumn, $childColumn, array $parentIds, $extraWhere = null)
    {
        if (!$parentIds) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName($table),
                ['parent_id' => $parentColumn, 'child_id' => $childColumn]
            )
            ->where($parentColumn . ' IN (?)', $parentIds)
            ->order([$parentColumn . ' ASC', $childColumn . ' ASC']);
        if ($extraWhere) {
            $select->where($extraWhere);
        }

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int)$row['parent_id']][] = (int)$row['child_id'];
        }
        return $result;
    }

    private function getRequiredAttributes(FeedProfileInterface $profile)
    {
        $attributes = [
            'sku',
            'name',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'image',
            'small_image',
            'thumbnail',
            'color',
            'size',
            'manufacturer',
        ];
        $mapping = json_decode((string)$profile->getAttributesMappingSerialized(), true);
        foreach (is_array($mapping) ? $mapping : [] as $field) {
            $code = $field['magento_attribute'] ?? '';
            if (preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
                $attributes[] = $code;
            }
        }
        return array_values(array_unique($attributes));
    }
}
