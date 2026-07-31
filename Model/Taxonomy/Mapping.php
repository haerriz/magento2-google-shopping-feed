<?php
namespace Haerriz\GoogleShoppingFeed\Model\Taxonomy;

use Magento\Framework\App\ResourceConnection;

class Mapping
{
    private $connection;
    private $cache = [];

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Resolve a Magento category ID to a Google taxonomy path.
     * Falls back to a safe default if not mapped.
     */
    public function resolveCategoryPath(int $categoryId): string
    {
        if (isset($this->cache[$categoryId])) {
            return $this->cache[$categoryId];
        }

        try {
            $table = $this->connection->getTableName('haerriz_google_shopping_feed_category_mapping');
            $path = $this->connection->fetchOne(
                "SELECT taxonomy_path FROM {$table} WHERE category_id = :category_id LIMIT 1",
                [':category_id' => $categoryId]
            );
            $result = $path ?: 'Apparel & Accessories';
        } catch (\Exception $e) {
            // Table may not exist yet (module upgrade in progress)
            $result = 'Apparel & Accessories';
        }

        $this->cache[$categoryId] = $result;
        return $result;
    }

    /**
     * Add or update a category → taxonomy mapping.
     */
    public function setMapping(int $categoryId, string $taxonomyPath): void
    {
        try {
            $table = $this->connection->getTableName('haerriz_google_shopping_feed_category_mapping');
            $this->connection->insertOnDuplicate($table, [
                'category_id'   => $categoryId,
                'taxonomy_path' => $taxonomyPath,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $this->cache[$categoryId] = $taxonomyPath;
        } catch (\Exception $e) {
            // Silent — mapping is optional
        }
    }
}
