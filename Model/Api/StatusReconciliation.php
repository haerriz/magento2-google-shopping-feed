<?php
namespace Haerriz\GoogleShoppingFeed\Model\Api;

class StatusReconciliation
{
    public function reconcile(): array
    {
        return ['reconciled' => true, 'status' => 'synced'];
    }
}
