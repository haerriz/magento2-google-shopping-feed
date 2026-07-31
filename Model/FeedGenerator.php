<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;

class FeedGenerator
{
    private $exporter;

    public function __construct(FeedExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    public function generate(FeedProfileInterface $profile, string $triggerSource = 'manual'): array
    {
        // IMPORTANT: FeedExporter uses Magento's MEDIA directory as root.
        // The path passed here must be RELATIVE to pub/media/, NOT include pub/media/.
        $filename = ltrim((string)$profile->getFilename(), '/');
        $filename = preg_replace('#^pub/media/#', '', $filename); // strip any accidental prefix
        
        return $this->exporter->export($profile, $filename);
    }
}
