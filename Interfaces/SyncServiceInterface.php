<?php

namespace NTI\SyncBundle\Interfaces;
use Doctrine\ORM\EntityManagerInterface;
use NTI\SyncBundle\Entity\SyncMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Interface SyncServiceInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncServiceInterface {

    /**
     * This function will take care of the synchronization. It will receive
     * both the array of data and also a transactional EntityManagerInterface
     * with which the database operations should be performed with.
     *
     * @param $data
     * @param EntityManagerInterface $em
     * @param SyncMapping $mapping
     * @param array $serializationGroups
     * @return mixed
     */
    public function sync($data, EntityManagerInterface $em, SyncMapping $mapping, $serializationGroups = array("sync_basic"));

    /**
     * This function will be called if an exception is thrown inside the sync() function.
     *
     * @param \Exception $exception
     * @param ContainerInterface $container
     * @return mixed
     */
    public function onSyncException(\Exception $exception, ContainerInterface $container);
}
