<?php
namespace Haerriz\GoogleShoppingFeed\Model\Cron;

use Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedProfile\CollectionFactory;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;
use Haerriz\GoogleShoppingFeed\Model\FeedGenerator;
use Haerriz\GoogleShoppingFeed\Model\Cron\Scheduler;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Lock\LockManagerInterface;

class Dispatcher
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var FeedProfileRepositoryInterface
     */
    protected $repository;

    /**
     * @var FeedGenerator
     */
    protected $generator;

    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DateTime
     */
    protected $date;

    private $lockManager;

    /**
     * @param CollectionFactory $collectionFactory
     * @param FeedProfileRepositoryInterface $repository
     * @param FeedGenerator $generator
     * @param Scheduler $scheduler
     * @param LoggerInterface $logger
     * @param DateTime $date
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        FeedProfileRepositoryInterface $repository,
        FeedGenerator $generator,
        Scheduler $scheduler,
        LoggerInterface $logger,
        DateTime $date,
        LockManagerInterface $lockManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->repository = $repository;
        $this->generator = $generator;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
        $this->date = $date;
        $this->lockManager = $lockManager;
    }

    /**
     * Discover and execute due feed generation schedules
     *
     * @return void
     */
    public function dispatch()
    {
        $nowUtc = $this->date->gmtDate();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 1);

        foreach ($collection as $profile) {
            // Check next run time
            $nextRun = $profile->getNextRunAt();
            if ($nextRun && strtotime($nextRun) > strtotime($nowUtc)) {
                continue; // Not due yet
            }

            // Stale Lock Recovery (older than 2 hours)
            if ($profile->getIsLocked() && $profile->getLockedAt()) {
                $lockedTime = strtotime($profile->getLockedAt());
                if (strtotime($nowUtc) - $lockedTime > 7200) {
                    $this->logger->warning(sprintf("Stale job lock recovered for profile: %s", $profile->getId()));
                    $profile->setIsLocked(0);
                }
            }

            // Concurrency policy checks
            if ($profile->getIsLocked()) {
                $policy = $profile->getConcurrencyPolicy() ?: 'skip';
                if ($policy === 'skip') {
                    $this->logger->info(sprintf("Profile %s is locked. Concurrency policy: SKIP.", $profile->getId()));
                    continue;
                }
                if ($policy === 'replace') {
                    $this->logger->info(sprintf("Profile %s is locked. Concurrency policy: REPLACE.", $profile->getId()));
                    $profile->setIsLocked(0);
                }
            }

            $this->runProfileJob($profile);
        }
    }

    /**
     * Run generation job for profile
     *
     * @param \Haerriz\GoogleShoppingFeed\Model\FeedProfile $profile
     * @return void
     */
    protected function runProfileJob($profile)
    {
        $nowUtc = $this->date->gmtDate();
        $lockName = 'haerriz_google_feed_profile_' . (int)$profile->getId();
        if (!$this->lockManager->lock($lockName, 0)) {
            $this->logger->info(sprintf('Profile %s already has an active process.', $profile->getId()));
            return;
        }

        try {
            $profile->setIsLocked(1);
            $profile->setLockedAt($nowUtc);
            $this->repository->save($profile);
            $startTime = microtime(true);
            $success = $this->generator->generate($profile, 'cron');
            $duration = round(microtime(true) - $startTime, 2);

            if ($success) {
                $profile->setConsecutiveFailures(0);
                $profile->setRetryCount(0);
                $profile->setIsLocked(0);

                // Calculate next run date
                $nextRun = $this->scheduler->calculateNextRun(
                    $profile->getFrequency(),
                    $profile->getCronExpression(),
                    $profile->getTimezone(),
                    $nowUtc
                );
                $profile->setNextRunAt($nextRun);
                $this->repository->save($profile);

                $this->logger->info(sprintf("Profile %s generated successfully in %s seconds.", $profile->getId(), $duration));
            } else {
                $this->handleFailure($profile);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf("Profile %s failed: %s", $profile->getId(), $e->getMessage()));
            $this->handleFailure($profile);
        } finally {
            try {
                $profile->setIsLocked(0);
                $profile->setLockedAt(null);
                $this->repository->save($profile);
            } finally {
                $this->lockManager->unlock($lockName);
            }
        }
    }

    /**
     * Handle job failure with retry rules and dynamic backoffs
     *
     * @param \Haerriz\GoogleShoppingFeed\Model\FeedProfile $profile
     * @return void
     */
    protected function handleFailure($profile)
    {
        $nowUtc = $this->date->gmtDate();
        $retryCount = $profile->getRetryCount() + 1;
        $maxRetries = $profile->getMaxRetries() ?: 3;

        $profile->setIsLocked(0);
        $profile->setRetryCount($retryCount);

        if ($retryCount <= $maxRetries) {
            // Exponential backoff: 2^retry_count minutes
            $backoffMinutes = pow(2, $retryCount);
            $nextRun = date('Y-m-d H:i:s', strtotime($nowUtc . " +{$backoffMinutes} minutes"));
            $profile->setNextRunAt($nextRun);
            $this->logger->info(sprintf("Profile %s marked for retry %s in %s minutes.", $profile->getId(), $retryCount, $backoffMinutes));
        } else {
            $profile->setRetryCount(0);
            $failures = $profile->getConsecutiveFailures() + 1;
            $profile->setConsecutiveFailures($failures);

            if ($failures >= 5) {
                $profile->setStatus(0); // Disable schedule
                $this->logger->warning(sprintf("Profile %s disabled due to 5 consecutive failure runs.", $profile->getId()));
            }

            // Re-schedule for next period
            $nextRun = $this->scheduler->calculateNextRun(
                $profile->getFrequency(),
                $profile->getCronExpression(),
                $profile->getTimezone(),
                $nowUtc
            );
            $profile->setNextRunAt($nextRun);
        }

        $this->repository->save($profile);
    }
}
