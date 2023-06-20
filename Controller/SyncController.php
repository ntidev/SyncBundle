<?php

namespace NTI\SyncBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use NTI\SyncBundle\Interfaces\SyncServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use NTI\SyncBundle\Repository\SyncStateRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use NTI\SyncBundle\Entity\SyncMapping;
use NTI\SyncBundle\Service\SyncService;
use NTI\SyncBundle\Service\PushService;
use NTI\SyncBundle\Repository\SyncMappingRepository;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SyncController.
 */
class SyncController extends AbstractController
{
    private $syncStateRepository;
    private $syncMappingRepository;
    private $serializer;
    private $syncService;
    private $pushService;

    public function __construct(SyncStateRepository        $syncStateRepository, SyncMappingRepository $syncMappingRepository,
                                SerializerInterface        $serializer, SyncService $syncService, PushService $pushService)
    {
        $this->syncStateRepository = $syncStateRepository;
        $this->syncMappingRepository = $syncMappingRepository;
        $this->serializer = $serializer;
        $this->syncService = $syncService;
        $this->pushService = $pushService;
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
     * @Route("/pull", name="nti_sync_pull", methods={"GET|POST"})
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
     */
    public function pushAction(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $result = $this->pushService->push($data);
            return new JsonResponse($result, 200);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}
