<?php
namespace Haerriz\GoogleShoppingFeed\Model\Generation;

class FailureClassifier
{
    public function classify(\Throwable $t): string { return 'general_error'; }
}
