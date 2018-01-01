<?php

namespace NTI\SyncBundle\Service;

use Doctrine\ORM\EntityManager;
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

    }

    /**
     * Get the list of changes and delete entries since this timestamp
     *
     * @param $mappings
     * @return array
     */
    public function getFromMappings($mappings) {

        $this->em = $this->container->get('doctrine')->getManager();

        $changes = array();

        foreach($mappings as $mapping) {

            if(!isset($mapping["timestamp"]) || !isset($mapping["mapping"])) {
                continue;
            }

            $timestamp = $mapping["timestamp"];
            $mappingName = $mapping["mapping"];
            $serializationGroup = (isset($mapping["serializer"])) ? $mapping["serializer"] : "sync_basic";

            $syncMapping = $this->em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("name" => $mappingName));

            if(!$syncMapping) {
                continue;
            }

            $deletes = $this->em->getRepository('NTISyncBundle:SyncDeleteState')->findFromTimestamp($mappingName, $timestamp);

            /** @var SyncRepositoryInterface $repository */
            $repository = $this->em->getRepository($syncMapping->getClass());
            if(!($repository instanceof SyncRepositoryInterface)) {
                error_log("The repository for the class {$mapping->getClass()} does not implement the SyncRepositoryInterface.");
                continue;
            }

            $result = $repository->findFromTimestamp($timestamp, $this->container, $serializationGroup);

            $changes[$mappingName] = array(
                'changes' => $result["data"],
                'deletes' => json_decode($this->container->get('serializer')->serialize($deletes, 'json'), true),
                SyncState::REAL_LAST_TIMESTAMP => $result[SyncState::REAL_LAST_TIMESTAMP],
            );
        }

        return $changes;
    }

    public function updateSyncState($class, $timestamp) {

        $this->em = $this->container->get('doctrine')->getManager();

        $mapping = $this->em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("class" => $class));
        if(!$mapping) {
            return;
        }

        $syncState = $this->em->getRepository('NTISyncBundle:SyncState')->findOneBy(array("mapping" => $mapping));

        if(!$syncState) {
            $syncState = new SyncState();
            $syncState->setMapping($mapping);
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

        $this->em = $this->container->get('doctrine')->getManager();

        $mapping = $this->em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("class" => $class));
        if(!$mapping) {
            return;
        }

        $deleteEntry = new SyncDeleteState();
        $deleteEntry->setMapping($mapping);
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