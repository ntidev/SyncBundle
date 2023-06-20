<?php

namespace NTI\SyncBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use NTI\SyncBundle\Entity\SyncDeleteState;
use NTI\SyncBundle\Entity\SyncMapping;
use NTI\SyncBundle\Interfaces\SyncRepositoryInterface;
use NTI\SyncBundle\Models\SyncPullRequestData;
use NTI\SyncBundle\Repository\SyncDeleteStateRepository;
use NTI\SyncBundle\Repository\SyncFailedItemStateRepository;
use NTI\SyncBundle\Repository\SyncMappingRepository;
use NTI\SyncBundle\Repository\SyncNewItemStateRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SyncService
 * @package NTI\SyncBundle\Service
 */
class SyncService
{

    /** @var ContainerInterface $container */
    private $container;
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var SerializerInterface */
    private $serializer;
    /** @var SyncMappingRepository */
    private $syncMappingRepository;
    /** @var SyncDeleteStateRepository */
    private $syncDeleteStateRepository;
    /** @var SyncNewItemStateRepository */
    private $syncNewItemStateRepository;
    /** @var SyncFailedItemStateRepository */
    private $syncFailedItemStateRepository;

    /**
     * SyncService constructor.
     */
    public function __construct(
        ContainerInterface            $container,
        SerializerInterface           $serializer,
        SyncMappingRepository         $syncMappingRepository,
        SyncDeleteStateRepository     $syncDeleteStateRepository,
        SyncNewItemStateRepository    $syncNewItemStateRepository,
        SyncFailedItemStateRepository $syncFailedItemStateRepository)
    {
        $this->container = $container;
        $this->serializer = $serializer;
        $this->syncMappingRepository = $syncMappingRepository;
        $this->syncDeleteStateRepository = $syncDeleteStateRepository;
        $this->syncNewItemStateRepository = $syncNewItemStateRepository;
        $this->syncFailedItemStateRepository = $syncFailedItemStateRepository;
    }

    /**
     * Get the list of changes and delete entries since this timestamp
     *
     * @param $mappings
     * @return array
     */
    public function getFromMappings($mappings)
    {

        $this->em = $this->container->get('doctrine')->getManager();

        $changes = array();

        foreach ($mappings as $mapping) {

            $requestData = $this->serializer->deserialize(json_encode($mapping), SyncPullRequestData::class, 'json');

            $syncMapping = $this->syncMappingRepository->findOneBy(array("name" => $requestData->getMapping()));

            if (!$syncMapping) {
                continue;
            }

            $deletes = $this->syncDeleteStateRepository->findFromTimestamp($requestData->getMapping(), $requestData->getTimestamp());
            $newItems = $this->syncNewItemStateRepository->findFromTimestampAndMapping($requestData->getMapping(), $requestData->getTimestamp());
            $failedItems = $this->syncFailedItemStateRepository->findFromTimestampAndMapping($requestData->getMapping(), $requestData->getTimestamp());

            /** @var SyncRepositoryInterface $repository */
            $repository = $this->em->getRepository($syncMapping->getClass());

            if (!($repository instanceof SyncRepositoryInterface)) {
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
    public function addToDeleteSyncState($class, $id)
    {

        $this->em = $this->container->get('doctrine')->getManager();

        /** @var SyncMapping $mapping */
        $mapping = $this->em->getRepository(SyncMapping::class)->findOneBy(array("class" => $class));
        if (!$mapping) {
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
