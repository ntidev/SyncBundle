<?php

namespace NTI\SyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class SyncNewItemState
 * @package NTI\SyncBundle\Entity
 * @ORM\Table(name="nti_sync_new_item_state")
 * @ORM\Entity()
 *
 */
class SyncNewItemState {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     */
    private $uuid;

    /**
     * @var SyncMapping
     *
     * @ORM\ManyToOne(targetEntity="NTI\SyncBundle\Entity\SyncMapping")
     */
    private $mapping;

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return SyncNewItemState
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return SyncNewItemState
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
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
     * @return SyncNewItemState
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
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
     * @return SyncNewItemState
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
     * @return SyncNewItemState
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

}