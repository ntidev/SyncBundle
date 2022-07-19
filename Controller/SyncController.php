<?php

namespace NTI\SyncBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use NTI\SyncBundle\Interfaces\SyncServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SyncController.
 */
class SyncController extends AbstractController
{
    /**
     * @return JsonResponse
     * @Route("/summary", name="nti_sync_get_summary")
     */
    public function getChangesSummaryAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $syncStates = $em->getRepository('NTISyncBundle:SyncState')->findBy([], ['mapping' => 'asc']);
        $syncStatesArray = json_decode($this->get('jms_serializer')->serialize($syncStates, 'json'), true);

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

        $resultData = $this->get('nti.sync')->getFromMappings($mappings);

        $resultData = json_decode($this->container->get('jms_serializer')->serialize($resultData, 'json'), true);

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

            $mapping = $em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(['name' => $mappingName]);

            if (!$mapping) {
                continue;
            }

            $syncClass = $mapping->getSyncService();

            /** @var SyncServiceInterface $service */
            $service = $this->get($syncClass);

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

        return new JsonResponse([
            'mappings' => $results,
        ]);
    }
}
