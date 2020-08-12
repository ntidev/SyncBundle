<?php

namespace NTI\SyncBundle\Models;

use JMS\Serializer\Annotation as JMS;

/**
 * Class SyncPullRequestData
 * @package NTI\SyncBundle\Models
 */
class SyncPullRequestData {

    /**
     * @var string
     * @JMS\Type("string")
     *
     * The name of the SyncMapping
     */
    private $mapping;

    /**
     * @var int
     * @JMS\Type("integer")
     *
     * The timestamp that the client wants to pull from
     */
    private $timestamp;

    /**
     * @var int
     * @JMS\Type("integer")
     *
     * The amount of results that should be returned
     */
    private $limit;

    /**
     * @var int
     * @JMS\Type("integer")
     *
     * The page for the current timestamp.
     *
     * Explanation: Let's say that the server is going to send results in batch of 50s
     * If there are 300 results whose last_timestamp is > than the timestamp provided
     * and all of those 300 results have the same timestamp (for example, it is normal to
     * set the inital timestamp to 0 when first installing this bundle) this would cause
     * a loop and the client would always sync the same 50 results over and over again.
     *
     * For this, the client can send the `page` parameter, which then can be used in the repository to offset the results.
     *
     */
    private $page = 1;

    /**
     * @var array|string
     * @JMS\Type("array")
     *
     * The serialization groups that the process should use when returning the results
     */
    private $serializationGroups = array("sync_basic");

    /**
     * @return string
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param string $mapping
     * @return SyncPullRequestData
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return SyncPullRequestData
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return SyncPullRequestData
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return SyncPullRequestData
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getSerializationGroups()
    {
        return $this->serializationGroups;
    }

    /**
     * @param array|string $serializationGroups
     * @return SyncPullRequestData
     */
    public function setSerializationGroups($serializationGroups)
    {
        $this->serializationGroups = $serializationGroups;
        return $this;
    }


}