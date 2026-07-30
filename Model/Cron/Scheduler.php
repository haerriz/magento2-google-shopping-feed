<?php
namespace Haerriz\GoogleShoppingFeed\Model\Cron;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;

class Scheduler
{
    public function isDue(FeedProfileInterface $profile): bool
    {
        return (bool)$profile->getStatus();
    }
}
