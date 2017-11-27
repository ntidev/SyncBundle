<?php

namespace NTI\SyncBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use NTI\SyncBundle\Entity\SyncDeleteState;
use NTI\SyncBundle\Entity\SyncState;
use NTI\SyncBundle\Interfaces\SyncRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SyncService
 * @package NTI\SyncBundle\Service
 */
class SyncService {

    /** @var ContainerInterface $container */
    private $container;

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * SyncService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * Get the list of changes and delete entries since this timestamp
     *
     * @param $timestamp
     * @return array
     */
    public function getFromTimestamp($timestamp) {
        $states = $this->em->getRepository('NTISyncBundle:SyncState')->findFromTimestamp($timestamp);
        $deletes = $this->em->getRepository('NTISyncBundle:SyncDeleteState')->findFromTimestamp($timestamp);

        $changes = array();

        /** @var SyncState $state */
        foreach($states as $state) {
            $mapping = $state->getSyncMapping();

            /** @var SyncRepositoryInterface $repository */
            $repository = $this->em->getRepository($mapping->getClass());
            if(!($repository instanceof SyncRepositoryInterface)) {
                error_log("The repository for the class {$mapping->getClass()} does not implement the SyncRepositoryInterface.");
                continue;
            }

            $objects = $repository->findFromTimestamp($timestamp, $this->container);

            if(count($objects) <= 0) {
                continue;
            }

            if(!isset($changes[$mapping->getMapName()])) {
                $changes[$mapping->getMapName()] = array();
            }

            $changes[$mapping->getMapName()][] = $objects;
        }

        return array(
            'changes' => $changes,
            'deletes' => json_decode($this->container->get('serializer')->serialize($deletes, 'json'), true),
        );
    }

    public function updateSyncState($class, $timestamp) {
        $mapping = $this->em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("class" => $class));
        if(!$mapping) {
            return;
        }

        $syncState = $this->em->getRepository('NTISyncBundle:SyncState')->findOneBy(array("syncMapping" => $mapping));

        if(!$syncState) {
            $syncState = new SyncState();
            $syncState->setSyncMapping($mapping);
            $this->em->persist($syncState);
        }

        $syncState->setTimestamp($timestamp);

        try {
            $this->em->flush();
        } catch (\Exception $ex) {
            error_log("Unable to register sync state change for object: " . $class);
            error_log($ex->getMessage());
        }
    }

    /**
     * Create a new SyncDeleteState for the given class/id
     *
     * @param $class
     * @param $id
     */
    public function addToDeleteSyncState($class, $id) {
        $mapping = $this->em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("class" => $class));

        if(!$mapping) {
            return;
        }

        $deleteEntry = new SyncDeleteState();
        $deleteEntry->setSyncMapping($mapping);
        $deleteEntry->setClassId($id);
        $deleteEntry->setTimestamp(time());

        $this->em->persist($deleteEntry);

        try {
            $this->em->flush();
        } catch (\Exception $ex) {
            error_log("Unable to register deletion of object: " . $class . " with ID " . $id);
            error_log($ex->getMessage());
        }
    }
}