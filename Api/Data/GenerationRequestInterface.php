<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface GenerationRequestInterface
{
    public function getProfileId(): int;
    public function getTrigger(): string;
}
