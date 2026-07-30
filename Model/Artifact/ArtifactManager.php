<?php
namespace Haerriz\GoogleShoppingFeed\Model\Artifact;

use Haerriz\GoogleShoppingFeed\Api\ArtifactManagerInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\ArtifactInterface;

class ArtifactManager implements ArtifactManagerInterface
{
    public function createArtifact(int $jobId, string $filename, string $content): ArtifactInterface
    {
        return new Artifact($filename, '/tmp/' . $filename, strlen($content));
    }
}
