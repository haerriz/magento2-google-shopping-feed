<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface FeedArtifactInterface
{
    public function getId();
    public function getJobId(): int;
    public function getFilename(): string;
}
