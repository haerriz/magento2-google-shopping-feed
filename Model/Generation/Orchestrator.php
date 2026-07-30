<?php
namespace Haerriz\GoogleShoppingFeed\Model\Generation;

use Haerriz\GoogleShoppingFeed\Api\GenerationOrchestratorInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\Data\GenerationResultInterface;

class Orchestrator implements GenerationOrchestratorInterface
{
    private \Haerriz\GoogleShoppingFeed\Model\FeedGenerator $generator;

    public function __construct(\Haerriz\GoogleShoppingFeed\Model\FeedGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generate(FeedProfileInterface $profile, string $trigger = 'manual'): GenerationResultInterface
    {
        try {
            $this->generator->generate($profile, $trigger);
            return new GenerationResult(true, 1, 100);
        } catch (\Exception $e) {
            return new GenerationResult(false, 0, 0, $e->getMessage());
        }
    }
}
