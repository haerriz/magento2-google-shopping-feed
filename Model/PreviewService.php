<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductProviderInterface;
use Haerriz\GoogleShoppingFeed\Api\ProductTypeResolverInterface;
use Haerriz\GoogleShoppingFeed\Model\Mapping\RowBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class PreviewService
{
    private Filesystem $filesystem;
    private FeedExporter $exporter;
    private ProfileValidator $validator;
    private ProductProviderInterface $productProvider;
    private ProductTypeResolverInterface $productTypeResolver;
    private RowBuilder $rowBuilder;
    private RuleFactory $ruleFactory;

    public function __construct(
        Filesystem $filesystem,
        FeedExporter $exporter,
        ProfileValidator $validator,
        ProductProviderInterface $productProvider,
        ProductTypeResolverInterface $productTypeResolver,
        RowBuilder $rowBuilder,
        RuleFactory $ruleFactory
    ) {
        $this->filesystem = $filesystem;
        $this->exporter = $exporter;
        $this->validator = $validator;
        $this->productProvider = $productProvider;
        $this->productTypeResolver = $productTypeResolver;
        $this->rowBuilder = $rowBuilder;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Build in-memory sample rows for Quick View (no file write required).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildSample(FeedProfileInterface $profile, int $limit = 10): array
    {
        $this->assertProfileValid($profile);
        $limit = max(1, min(100, $limit));

        $rule = $this->createRule($profile);
        $collection = $this->productProvider->getCollection($profile, $rule, 0, max($limit * 3, 15));
        $this->productTypeResolver->prepare($collection, $profile);

        $rows = [];
        foreach ($collection as $product) {
            foreach ($this->productTypeResolver->resolve($product, $profile) as $feedProduct) {
                if ($rule && !$rule->getConditions()->validate($feedProduct)) {
                    continue;
                }
                try {
                    $rows[] = $this->rowBuilder->build($feedProduct, $profile);
                } catch (\Throwable $e) {
                    continue;
                }
                if (count($rows) >= $limit) {
                    break 2;
                }
            }
        }

        return $rows;
    }

    public function preview(FeedProfileInterface $profile, $limit = 10)
    {
        $this->assertProfileValid($profile);
        $limit = max(1, min(100, (int)$limit));

        $extension = $this->resolveExtension($profile);
        $path = 'google_feed/preview/' . bin2hex(random_bytes(16)) . '.' . $extension;
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $directory->create('google_feed/preview');

        try {
            $counts = $this->exporter->export($profile, $path, null, $limit);
            $content = $directory->isExist($path) ? $directory->readFile($path) : '';
            return [
                'sampled' => true,
                'limit' => $limit,
                'counts' => $counts,
                'format' => $profile->getFeedType(),
                'extension' => $extension,
                'content' => $content,
            ];
        } finally {
            if ($directory->isExist($path)) {
                $directory->delete($path);
            }
        }
    }

    public function generatePreview(FeedProfileInterface $profile, $limit = 10)
    {
        return $this->preview($profile, $limit);
    }

    private function assertProfileValid(FeedProfileInterface $profile): void
    {
        if (method_exists($this->validator, 'assertValid')) {
            $this->validator->assertValid($profile);
            return;
        }

        $errors = $this->validator->validate($profile);
        // Preview can run with empty mapping (defaults applied), but hard failures should stop.
        $blocking = array_filter($errors, static function ($error) {
            $message = (string)$error;
            return !str_contains(strtolower($message), 'mapping');
        });
        if ($blocking) {
            throw new \InvalidArgumentException(implode(' ', $blocking));
        }
    }

    private function createRule(FeedProfileInterface $profile)
    {
        $serialized = $profile->getConditionsSerialized();
        if (!$serialized) {
            return null;
        }
        $conditions = json_decode($serialized, true);
        if (!$conditions) {
            return null;
        }
        $rule = $this->ruleFactory->create();
        $rule->getConditions()->loadArray($conditions);
        return $rule;
    }

    private function resolveExtension(FeedProfileInterface $profile): string
    {
        $filename = (string)$profile->getFilename();
        $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['xml', 'csv', 'tsv', 'txt', 'jsonl', 'json'], true)) {
            return $ext === 'json' ? 'jsonl' : $ext;
        }

        $feedType = strtolower((string)$profile->getFeedType());
        if (str_contains($feedType, 'json')) {
            return 'jsonl';
        }
        if (str_contains($feedType, 'csv') || str_contains($feedType, 'meta') || str_contains($feedType, 'tiktok')
            || str_contains($feedType, 'amazon') || str_contains($feedType, 'ebay') || str_contains($feedType, 'pinterest')
            || str_contains($feedType, 'snapchat') || str_contains($feedType, 'instagram') || str_contains($feedType, 'rakuten')
        ) {
            return 'csv';
        }

        return 'xml';
    }
}
