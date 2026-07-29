<?php
namespace Haerriz\GoogleShoppingFeed\Api\Data;

interface FeedProfileInterface
{
    const PROFILE_ID = 'profile_id';
    const NAME = 'name';
    const STATUS = 'status';
    const STORE_ID = 'store_id';
    const FILENAME = 'filename';
    const FEED_TYPE = 'feed_type';
    const CONDITIONS_SERIALIZED = 'conditions_serialized';
    const ATTRIBUTES_MAPPING_SERIALIZED = 'attributes_mapping_serialized';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const CURRENCY = 'currency';

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
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return int|null
     */
    public function getStatus();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int|null
     */
    public function getStoreId();

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @return string|null
     */
    public function getFilename();

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename);

    /**
     * @return string|null
     */
    public function getFeedType();

    /**
     * @param string $feedType
     * @return $this
     */
    public function setFeedType($feedType);

    /**
     * @return string|null
     */
    public function getConditionsSerialized();

    /**
     * @param string $conditionsSerialized
     * @return $this
     */
    public function setConditionsSerialized($conditionsSerialized);

    /**
     * @return string|null
     */
    public function getAttributesMappingSerialized();

    /**
     * @param string $attributesMappingSerialized
     * @return $this
     */
    public function setAttributesMappingSerialized($attributesMappingSerialized);

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
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
