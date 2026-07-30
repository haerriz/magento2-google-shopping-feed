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
        $path = 'pub/media/' . $profile->getFilename();
        return $this->exporter->export($profile, $path);
    }
}
