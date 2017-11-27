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
    private $syncMapping;

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
    public function getSyncMapping()
    {
        return $this->syncMapping;
    }

    /**
     * @param mixed $syncMapping
     * @return SyncState
     */
    public function setSyncMapping($syncMapping)
    {
        $this->syncMapping = $syncMapping;
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

