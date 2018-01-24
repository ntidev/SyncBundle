<?php

namespace NTI\SyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class SyncFailedItemState
 * @package NTI\SyncBundle\Entity
 * @ORM\Table(name="nti_sync_failed_item_state")
 * @ORM\Entity(repositoryClass="NTI\SyncBundle\Repository\SyncFailedItemStateRepository")
 *
 */
class SyncFailedItemState {

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
     * @ORM\Column(name="uuid", type="string", length=255, nullable=false, unique=true)
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
     * @ORM\Column(name="class_id", type="integer", nullable=true)
     */
    private $classId;

    /**
     * @var integer
     *
     * @ORM\Column(name="timestamp", type="integer", nullable=false)
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="errors", columnDefinition="TEXT", length=65535, nullable=true)
     */
    private $errors;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return SyncFailedItemState
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set classId
     *
     * @param integer $classId
     *
     * @return SyncFailedItemState
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId
     *
     * @return integer
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * Set timestamp
     *
     * @param integer $timestamp
     *
     * @return SyncFailedItemState
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set errors
     *
     * @param string $errors
     *
     * @return SyncFailedItemState
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get errors
     *
     * @return string
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set mapping
     *
     * @param \NTI\SyncBundle\Entity\SyncMapping $mapping
     *
     * @return SyncFailedItemState
     */
    public function setMapping(\NTI\SyncBundle\Entity\SyncMapping $mapping = null)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapping
     *
     * @return \NTI\SyncBundle\Entity\SyncMapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }
}
