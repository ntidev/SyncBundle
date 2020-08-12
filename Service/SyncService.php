<?php

namespace NTI\SyncBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use NTI\SyncBundle\Entity\SyncDeleteState;
use NTI\SyncBundle\Entity\SyncFailedItemState;
use NTI\SyncBundle\Entity\SyncMapping;
use NTI\SyncBundle\Entity\SyncNewItemState;
use NTI\SyncBundle\Entity\SyncState;
use NTI\SyncBundle\Interfaces\SyncRepositoryInterface;
use NTI\SyncBundle\Models\SyncPullRequestData;
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

            $requestData = $this->container->get('jms_serializer')->deserialize(json_encode($mapping), SyncPullRequestData::class, 'json');

            $syncMapping = $this->em->getRepository(SyncMapping::class)->findOneBy(array("name" => $requestData->getMapping()));

            if(!$syncMapping) {
                continue;
            }

            $deletes = $this->em->getRepository(SyncDeleteState::class)->findFromTimestamp($requestData->getMapping(), $requestData->getTimestamp());
            $newItems = $this->em->getRepository(SyncNewItemState::class)->findFromTimestampAndMapping($requestData->getMapping(), $requestData->getTimestamp());
            $failedItems = $this->em->getRepository(SyncFailedItemState::class)->findFromTimestampAndMapping($requestData->getMapping(), $requestData->getTimestamp());

            /** @var SyncRepositoryInterface $repository */
            $repository = $this->em->getRepository($syncMapping->getClass());
            if(!($repository instanceof SyncRepositoryInterface)) {
                error_log("The repository for the class {$syncMapping->getClass()} does not implement the SyncRepositoryInterface.");
                continue;
            }

            $result = $repository->findFromTimestamp($this->container, $requestData);

            $changes[$requestData->getMapping()] = array(
                'changes' => $result,
                'deletes' => json_decode($this->container->get('jms_serializer')->serialize($deletes, 'json'), true),
                'newItems' => json_decode($this->container->get('jms_serializer')->serialize($newItems, 'json'), true),
		        'failedItems' => json_decode($this->container->get('jms_serializer')->serialize($failedItems, 'json'), true),
            );
        }

        return $changes;
    }

    /**
     * Create a new SyncDeleteState for the given class/id
     *
     * @param $class
     * @param $id
     */
    public function addToDeleteSyncState($class, $id) {

        $this->em = $this->container->get('doctrine')->getManager();

        /** @var SyncMapping $mapping */
        $mapping = $this->em->getRepository(SyncMapping::class)->findOneBy(array("class" => $class));
        if(!$mapping) {
            return;
        }

        $deleteEntry = new SyncDeleteState();
        $deleteEntry->setMapping($mapping);
        $deleteEntry->setClassId($id);
        $deleteEntry->setTimestamp(time());

        $this->em->persist($deleteEntry);
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSet($this->em->getClassMetadata(SyncDeleteState::class), $deleteEntry);
    }
}
