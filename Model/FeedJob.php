<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Magento\Framework\Model\AbstractModel;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedJobInterface;

class FeedJob extends AbstractModel implements FeedJobInterface
{
    protected function _construct()
    {
        $this->_init(\Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedJob::class);
    }

    public function getId()
    {
        return $this->getData(self::JOB_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::JOB_ID, $id);
    }

    public function getProfileId()
    {
        return $this->getData(self::PROFILE_ID);
    }

    public function setProfileId($profileId)
    {
        return $this->setData(self::PROFILE_ID, $profileId);
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getTotalProducts()
    {
        return $this->getData(self::TOTAL_PRODUCTS);
    }

    public function setTotalProducts($totalProducts)
    {
        return $this->setData(self::TOTAL_PRODUCTS, $totalProducts);
    }

    public function getProcessedProducts()
    {
        return $this->getData(self::PROCESSED_PRODUCTS);
    }

    public function setProcessedProducts($processedProducts)
    {
        return $this->setData(self::PROCESSED_PRODUCTS, $processedProducts);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    public function setStartedAt($startedAt)
    {
        return $this->setData(self::STARTED_AT, $startedAt);
    }

    public function getFinishedAt()
    {
        return $this->getData(self::FINISHED_AT);
    }

    public function setFinishedAt($finishedAt)
    {
        return $this->setData(self::FINISHED_AT, $finishedAt);
    }
}
