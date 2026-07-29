<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface FeedJobInterface
{
    const JOB_ID = 'job_id';
    const PROFILE_ID = 'profile_id';
    const STATUS = 'status';
    const TOTAL_PRODUCTS = 'total_products';
    const PROCESSED_PRODUCTS = 'processed_products';
    const CREATED_AT = 'created_at';
    const STARTED_AT = 'started_at';
    const FINISHED_AT = 'finished_at';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getProfileId();

    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return string|null
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int|null
     */
    public function getTotalProducts();

    /**
     * @param int $totalProducts
     * @return $this
     */
    public function setTotalProducts($totalProducts);

    /**
     * @return int|null
     */
    public function getProcessedProducts();

    /**
     * @param int $processedProducts
     * @return $this
     */
    public function setProcessedProducts($processedProducts);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string|null
     */
    public function getStartedAt();

    /**
     * @param string $startedAt
     * @return $this
     */
    public function setStartedAt($startedAt);

    /**
     * @return string|null
     */
    public function getFinishedAt();

    /**
     * @param string $finishedAt
     * @return $this
     */
    public function setFinishedAt($finishedAt);
}
