<?php

namespace NTI\SyncBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use NTI\SyncBundle\Entity\SyncDeleteState;
use NTI\SyncBundle\Entity\SyncFailedItemState;
use NTI\SyncBundle\Entity\SyncMapping;
use NTI\SyncBundle\Entity\SyncNewItemState;
use NTI\SyncBundle\Entity\SyncState;
use NTI\SyncBundle\Interfaces\SyncRepositoryInterface;
use NTI\SyncBundle\Models\SyncPullRequestData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\Serializer\SerializerInterface;
use NTI\SyncBundle\Repository\SyncDeleteStateRepository;
use NTI\SyncBundle\Repository\SyncFailedItemStateRepository;
use NTI\SyncBundle\Repository\SyncMappingRepository;
use NTI\SyncBundle\Repository\SyncNewItemStateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PushService
 * @package NTI\SyncBundle\Service
 */
class PushService
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
     * @param $data
     * @return array
     */
    public function push($data)
    {
        $em = $this->container->get('doctrine')->getManager();
        $mappings = (isset($data['mappings'])) ? $data['mappings'] : [];

        $results = [];

        foreach ($mappings as $entry) {
            if (!$em->isOpen()) {
                try {
                    $em = EntityManager::create(
                        $em->getConnection(),
                        $em->getConfiguration(),
                        $em->getEventManager()
                    );
                } catch (ORMException $e) {
                    throw new \Exception("An unknown error occurred while reopening the database connection.", 500);
                }
            }

            if (!isset($entry['mapping']) || !isset($entry['data'])) {
                continue;
            }

            $mappingName = $entry['mapping'];

            $mapping = $this->syncMappingRepository->findOneBy(['name' => $mappingName]);

            if (!$mapping) {
                continue;
            }

            $syncClass = $mapping->getSyncService();

            /** @var SyncServiceInterface $service */
            $service = $this->container->get($syncClass);

            $em->beginTransaction();

            try {
                $result = $service->sync($entry['data'], $em, $mapping);
            } catch (\Exception $ex) {
                $additionalErrors = [];

                try {
                    $additionalErrors = $service->onSyncException($ex, $this->container);
                    $additionalErrors = (is_array($additionalErrors)) ? $additionalErrors : [];
                } catch (\Exception $ex) {
                    // TBD
                }

                $result = [
                    'error' => 'An unknown error occurred while processing the synchronization for this mapping',
                    'additional_errors' => $additionalErrors,
                ];

                $results[$mappingName] = $result;

                $em->clear();

                continue;
            }

            try {
                $em->flush();
                $em->commit();
                $results[$mappingName] = $result;
            } catch (\Exception $ex) {
                $em->rollback();

                $additionalErrors = [];

                try {
                    $additionalErrors = $service->onSyncException($ex, $this->container);
                    $additionalErrors = (is_array($additionalErrors)) ? $additionalErrors : [];
                } catch (\Exception $ex) {
                    // TBD
                }

                $result = [
                    'error' => 'An unknown error occurred while processing the synchronization for this mapping',
                    'additional_errors' => $additionalErrors,
                ];

                $results[$mappingName] = $result;
            }
        }

        return $results;
    }
}
