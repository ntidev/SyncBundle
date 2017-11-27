<?php

namespace NTI\SyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SyncDeleteState
 *
 * @ORM\Table(name="nti_sync_delete_state")
 * @ORM\Entity(repositoryClass="NTI\SyncBundle\Repository\SyncDeleteStateRepository")
 */
class SyncDeleteState
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
     * @ORM\Column(name="class_id", type="integer", nullable=false)
     */
    private $classId;

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
     * @param SyncMapping $syncMapping
     * @return SyncDeleteState
     */
    public function setSyncMapping($syncMapping)
    {
        $this->syncMapping = $syncMapping;
        return $this;
    }

    /**
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @param int $classId
     * @return SyncDeleteState
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
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
     * @return SyncDeleteState
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

}

