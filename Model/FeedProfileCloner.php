<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\FeedProfileRepositoryInterface;

class FeedProfileCloner
{
    /**
     * @var FeedProfileFactory
     */
    private $profileFactory;

    /**
     * @var FeedProfileRepositoryInterface
     */
    private $repository;

    public function __construct(
        FeedProfileFactory $profileFactory,
        FeedProfileRepositoryInterface $repository
    ) {
        $this->profileFactory = $profileFactory;
        $this->repository = $repository;
    }

    /**
     * Clone a profile without credentials, locks, schedule state, or identity.
     *
     * @param FeedProfileInterface $source
     * @return FeedProfileInterface
     */
    public function duplicate(FeedProfileInterface $source)
    {
        if (!$source instanceof FeedProfile) {
            throw new \InvalidArgumentException('Unsupported feed profile implementation.');
        }

        $copy = $this->profileFactory->create();
        $data = $source->getData();
        foreach ([
            'profile_id',
            'delivery_password',
            'created_at',
            'updated_at',
            'next_run_at',
            'is_locked',
            'locked_at',
            'retry_count',
            'consecutive_failures',
        ] as $field) {
            unset($data[$field]);
        }

        $copy->setData($data);
        $copy->setName(__('Copy of %1', $source->getName()));
        $copy->setStatus(0);

        return $this->repository->save($copy);
    }
}
