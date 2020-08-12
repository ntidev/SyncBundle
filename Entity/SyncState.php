<?php

namespace NTI\SyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SyncState
 *
 * @ORM\Table(name="nti_sync_state")
 * @ORM\Entity(repositoryClass="NTI\SyncBundle\Repository\SyncStateRepository")
 */
class SyncState
{
    const REAL_LAST_TIMESTAMP = "_real_last_timestamp";
    const TOTAL_COUNT = "_total_count";
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var SyncMapping
     *
     * @ORM\OneToOne(targetEntity="NTI\SyncBundle\Entity\SyncMapping")
     */
    private $mapping;

    /**
     * @var integer
     *
     * @ORM\Column(name="timestamp", type="integer", nullable=false)
     */
    private $timestamp;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SyncMapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param SyncMapping $mapping
     * @return SyncState
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
     * @return SyncState
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

}
