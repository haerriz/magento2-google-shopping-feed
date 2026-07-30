<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface ArtifactInterface
{
    public function getFilename(): string;
    public function getFilePath(): string;
    public function getSize(): int;
}
