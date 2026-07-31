<?php
namespace Haerriz\GoogleShoppingFeed\Model\Generation;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedExporter;
use Haerriz\GoogleShoppingFeed\Model\FeedJobFactory;
use Haerriz\GoogleShoppingFeed\Model\FeedJobRepository;
use Haerriz\GoogleShoppingFeed\Model\Generation\FailureClassifier;
use Haerriz\GoogleShoppingFeed\Model\Generation\ProfileLock;
use Psr\Log\LoggerInterface;

class Orchestrator
{
    private $exporter;
    private $jobFactory;
    private $jobRepository;
    private $failureClassifier;
    private $lock;
    private $logger;

    public function __construct(
        FeedExporter $exporter,
        FeedJobFactory $jobFactory,
        FeedJobRepository $jobRepository,
        FailureClassifier $failureClassifier,
        ProfileLock $lock,
        LoggerInterface $logger
    ) {
        $this->exporter          = $exporter;
        $this->jobFactory        = $jobFactory;
        $this->jobRepository     = $jobRepository;
        $this->failureClassifier = $failureClassifier;
        $this->lock              = $lock;
        $this->logger            = $logger;
    }

    /**
     * Full generation lifecycle:
     * 1. Acquire profile lock (prevent concurrent runs)
     * 2. Create FeedJob record (for monitoring)
     * 3. Export the feed
     * 4. Mark job as done or classify failure
     * 5. Release lock
     */
    public function run(FeedProfileInterface $profile, string $triggerSource = 'manual'): array
    {
        $profileId = (int)$profile->getId();

        // Acquire lock — skip if already running
        if (!$this->lock->acquire($profileId)) {
            $this->logger->warning("GoogleShoppingFeed: Profile #{$profileId} is already running. Skipping.");
            return ['skipped' => true, 'reason' => 'locked'];
        }

        $job = $this->jobFactory->create();
        $job->setProfileId($profileId);
        $job->setTriggerSource($triggerSource);
        $job->setStatus('running');
        $job->setStartedAt(date('Y-m-d H:i:s'));
        $this->jobRepository->save($job);

        try {
            // Strip pub/media prefix — FS root is already pub/media/
            $filename   = preg_replace('#^pub/media/#', '', ltrim((string)$profile->getFilename(), '/'));
            $result     = $this->exporter->export($profile, $filename, $job);

            $job->setStatus('done');
            $job->setFinishedAt(date('Y-m-d H:i:s'));
            $this->jobRepository->save($job);

            return $result;

        } catch (\Exception $e) {
            $category = $this->failureClassifier->classify($e);
            $job->setStatus('error');
            $job->setFinishedAt(date('Y-m-d H:i:s'));
            $job->setData('failure_category', $category);
            $job->setData('failure_message', $e->getMessage());
            $this->jobRepository->save($job);

            $this->logger->error("GoogleShoppingFeed: Profile #{$profileId} failed [{$category}]: " . $e->getMessage());
            throw $e;

        } finally {
            $this->lock->release($profileId);
        }
    }
}
