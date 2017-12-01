<?php

namespace NTI\SyncBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SyncMapping
 *
 * @ORM\Table(name="nti_sync_mapping")
 * @ORM\Entity(repositoryClass="NTI\SyncBundle\Repository\SyncMappingRepository")
 */
class SyncMapping
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="class", type="string", length=255, nullable=false, unique=true)
     */
    private $class;

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return SyncMapping
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return SyncMapping
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }
}

