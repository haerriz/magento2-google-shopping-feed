<?php
namespace Haerriz\GoogleShoppingFeed\Api;

interface JobLoggerInterface
{
    public function log(int $jobId, string $message, string $level = 'info');
}
