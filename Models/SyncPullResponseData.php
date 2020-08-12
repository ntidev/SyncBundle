<?php

namespace NTI\SyncBundle\Models;

use JMS\Serializer\Annotation as JMS;

/**
 * Class SyncPullResponseData
 * @package NTI\SyncBundle\Models
 *
 */
class SyncPullResponseData {

    /**
     * @var int
     * @JMS\SerializedName("real_last_timestamp")
     *
     * This is the last timestamp of the batch of results provided. Basically, it's the timestamp of the last element
     * in the array of provided elements in data. This is useful for the client so it can continue synching from this
     * timestamp.
     */
    private $realLastTimestamp;

    /**
     * @var array
     *
     * The list of elements that changed.
     */
    private $data;

    /**
     * @var int
     * @JMS\SerializedName("total_count")
     *
     * This is the total count of results from the timestamp provided. Useful for the client to know if there are more results and how many more.
     */
    private $totalCount;

    /**
     * @return int
     */
    public function getRealLastTimestamp(): int
    {
        return $this->realLastTimestamp;
    }

    /**
     * @param int $realLastTimestamp
     * @return SyncPullResponseData
     */
    public function setRealLastTimestamp(int $realLastTimestamp): SyncPullResponseData
    {
        $this->realLastTimestamp = $realLastTimestamp;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return SyncPullResponseData
     */
    public function setData(array $data): SyncPullResponseData
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     * @return SyncPullResponseData
     */
    public function setTotalCount(int $totalCount): SyncPullResponseData
    {
        $this->totalCount = $totalCount;
        return $this;
    }


}