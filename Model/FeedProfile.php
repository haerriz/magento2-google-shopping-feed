<?php
namespace Haerriz\GoogleShoppingFeed\Model;

use Magento\Framework\Model\AbstractModel;
use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;

class FeedProfile extends AbstractModel implements FeedProfileInterface
{
    protected function _construct()
    {
        $this->_init(\Haerriz\GoogleShoppingFeed\Model\ResourceModel\FeedProfile::class);
    }

    public function getId()
    {
        return $this->getData(self::PROFILE_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::PROFILE_ID, $id);
    }

    public function getName()
    {
        return $this->getData(self::NAME);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    public function getFilename()
    {
        return $this->getData(self::FILENAME);
    }

    public function setFilename($filename)
    {
        return $this->setData(self::FILENAME, $filename);
    }

    public function getFeedType()
    {
        return $this->getData(self::FEED_TYPE);
    }

    public function setFeedType($feedType)
    {
        return $this->setData(self::FEED_TYPE, $feedType);
    }

    public function getConditionsSerialized()
    {
        return $this->getData(self::CONDITIONS_SERIALIZED);
    }

    public function setConditionsSerialized($conditionsSerialized)
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditionsSerialized);
    }

    public function getAttributesMappingSerialized()
    {
        return $this->getData(self::ATTRIBUTES_MAPPING_SERIALIZED);
    }

    public function setAttributesMappingSerialized($attributesMappingSerialized)
    {
        return $this->setData(self::ATTRIBUTES_MAPPING_SERIALIZED, $attributesMappingSerialized);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
    public function getDeliveryType()
    {
        return $this->getData(self::DELIVERY_TYPE);
    }

    public function setDeliveryType($type)
    {
        return $this->setData(self::DELIVERY_TYPE, $type);
    }

    public function getDeliveryHost()
    {
        return $this->getData(self::DELIVERY_HOST);
    }

    public function setDeliveryHost($host)
    {
        return $this->setData(self::DELIVERY_HOST, $host);
    }

    public function getDeliveryPort()
    {
        return $this->getData(self::DELIVERY_PORT);
    }

    public function setDeliveryPort($port)
    {
        return $this->setData(self::DELIVERY_PORT, $port);
    }

    public function getDeliveryUsername()
    {
        return $this->getData(self::DELIVERY_USERNAME);
    }

    public function setDeliveryUsername($username)
    {
        return $this->setData(self::DELIVERY_USERNAME, $username);
    }

    public function getDeliveryPassword()
    {
        return $this->getData(self::DELIVERY_PASSWORD);
    }

    public function setDeliveryPassword($password)
    {
        return $this->setData(self::DELIVERY_PASSWORD, $password);
    }

    public function getDeliveryPath()
    {
        return $this->getData(self::DELIVERY_PATH);
    }

    public function setDeliveryPath($path)
    {
        return $this->setData(self::DELIVERY_PATH, $path);
    }
}
