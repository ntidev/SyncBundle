<?php

namespace NTI\SyncBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use NTI\SyncBundle\Interfaces\SyncServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use NTI\SyncBundle\Repository\SyncStateRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use NTI\SyncBundle\Entity\SyncMapping;
use NTI\SyncBundle\Service\SyncService;
use NTI\SyncBundle\Repository\SyncMappingRepository;
use Api\Services\Synchronization\InventoryReport\InventoryReportSyncService;
use Api\Services\Synchronization\Company\CompanySyncService;
use Api\Services\Synchronization\Contact\ContactSyncService;

/**
 * Class SyncController.
 */
class SyncController extends AbstractController
{
//    private $em;
    protected $container2;
    private $syncStateRepository;
    private $syncMappingRepository;
    private $serializer;
    private $syncService;
    private $inventoryReportSyncService;
    private $companySyncService;
    private $contactSyncService;

    public function __construct(SyncStateRepository        $syncStateRepository, SyncMappingRepository $syncMappingRepository,
                                SerializerInterface        $serializer, SyncService $syncService,
                                InventoryReportSyncService $inventoryReportSyncService, CompanySyncService $companySyncService,
                                ContactSyncService $contactSyncService, ContainerInterface $container2)
    {
        $this->syncStateRepository = $syncStateRepository;
        $this->syncMappingRepository = $syncMappingRepository;
        $this->serializer = $serializer;
        $this->syncService = $syncService;
        $this->inventoryReportSyncService = $inventoryReportSyncService;
        $this->companySyncService = $companySyncService;
        $this->contactSyncService = $contactSyncService;
        $this->container2 = $container2;
    }

    /**
     * @return JsonResponse
     * @Route("/summary", name="nti_sync_get_summary")
     */
    public function getChangesSummaryAction(Request $request)
    {
        $syncStates = $this->syncStateRepository->findBy([], ['mapping' => 'asc']);
        $syncStatesArray = json_decode($this->serializer->serialize($syncStates, 'json'));

        return new JsonResponse($syncStatesArray, 200);
    }

    /**
     * @return JsonResponse
     * @Route("/pull", name="nti_sync_pull")
     * @Method("GET|POST")
     */
    public function pullAction(Request $request)
    {
        $mappings = [];

        if ('GET' == $request->getMethod()) {
            $mappings = ($request->get('mappings') && is_array($request->get('mappings'))) ? $request->get('mappings') : [];
        } elseif ('POST' == $request->getMethod()) {
            $data = json_decode($request->getContent(), true);
            $mappings = (isset($data['mappings'])) ? $data['mappings'] : [];
        }

        $resultData = $this->syncService->getFromMappings($mappings);

        $resultData = json_decode($this->serializer->serialize($resultData, 'json'));

        return new JsonResponse($resultData, 200);
    }

    /**
     * @return JsonResponse
     * @Route("/push", name="nti_sync_push", methods="POST")
     * @Method("POST")
     */
    public function pushAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $mappings = (isset($data['mappings'])) ? $data['mappings'] : [];

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

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
                    return new JsonResponse(['error' => 'An unknown error occurred while reopening the database connection.'], 500);
                }
            }

            if (!isset($entry['mapping']) || !isset($entry['data'])) {
                continue;
            }

            $mappingName = $entry['mapping'];

            /** @var SyncMapping $mapping */
            $mapping = $this->syncMappingRepository->findOneBy(['name' => $mappingName]);

            if (!$mapping) {
                continue;
            }

            $service = '';
            if ($mapping->getName() === 'Inventory_Report') {
                $service = $this->inventoryReportSyncService;
            }
            if ($mapping->getName() === 'Company') {
                $service = $this->companySyncService;
            }
            if ($mapping->getName() === 'Contact') {
                $service = $this->contactSyncService;
            }

            if ($service == '') {
                return new JsonResponse(['error' => 'Service not found. Please inlude the service directly to the bundle in the SyncController Class.'], 400);
            }

            // $syncClass = $mapping->getSyncService();
             /** @var SyncServiceInterface $service */
            // $service = $this->get($syncClass);

            $em->beginTransaction();

            try {
                $result = $service->sync($entry['data'], $em, $mapping);
            } catch (\Exception $ex) {
                $additionalErrors = [];

                try {
                    $additionalErrors = $service->onSyncException($ex, $this->container2);
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
                    $additionalErrors = $service->onSyncException($ex, $this->container2);
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

        return new JsonResponse([
            'mappings' => $results,
        ]);
    }
}
