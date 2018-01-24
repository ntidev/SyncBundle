<?php

namespace NTI\SyncBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use NTI\SyncBundle\Entity\SyncNewItemState;
use NTI\SyncBundle\Interfaces\SyncServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Serializer\Serializer;

/**
 * Class SyncController
 * @package NTI\SyncBundle\Controller
 */
class SyncController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/summary", name="nti_sync_get_summary")
     */
    public function getChangesSummaryAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $syncStates = $em->getRepository('NTISyncBundle:SyncState')->findBy(array(), array("mapping" => "asc"));
        $syncStatesArray = json_decode($this->get("jms_serializer")->serialize($syncStates, 'json'), true);

        return new JsonResponse($syncStatesArray, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/pull", name="nti_sync_pull")
     * @Method("GET|POST")
     */
    public function pullAction(Request $request) {

        $mappings = array();

        if($request->getMethod() == "GET") {
            $mappings = ($request->get('mappings') && is_array($request->get('mappings'))) ? $request->get('mappings') : array();
        } elseif ($request->getMethod() == "POST") {
            $data = json_decode($request->getContent(), true);
            $mappings = (isset($data["mappings"])) ? $data["mappings"] : array();
        }

        $changes = $this->get('nti.sync')->getFromMappings($mappings);

        return new JsonResponse($changes, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/push", name="nti_sync_push", methods="POST")
     * @Method("POST")
     */
    public function pushAction(Request $request) {

        $data = json_decode($request->getContent(), true);

        $mappings = (isset($data["mappings"])) ? $data["mappings"] : array();

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        $results = array();

        foreach($mappings as $entry) {

            if (!$em->isOpen()) {
                try {
                    $em = EntityManager::create(
                        $em->getConnection(),
                        $em->getConfiguration(),
                        $em->getEventManager()
                    );
                } catch (ORMException $e) {
                    return new JsonResponse(array("error" => "An unknown error occurred while reopening the database connection."), 500);
                }
            }

            if(!isset($entry["mapping"]) || !isset($entry["data"])) { continue; }

            $mappingName = $entry["mapping"];

            $mapping = $em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("name" => $mappingName));

            if(!$mapping) { continue; }

            $syncClass = $mapping->getSyncService();

            #if(!class_exists($syncClass)) { continue; }

            /** @var SyncServiceInterface $service */
            $service = $this->get($syncClass);

            $em->beginTransaction();

            try {
                $result = $service->sync($entry["data"], $em, $mapping);
            } catch (\Exception $ex) {

                $additionalErrors = array();

                try {
                    $additionalErrors = $service->onSyncException($ex, $this->container);
                    $additionalErrors = (is_array($additionalErrors)) ? $additionalErrors : array();
                } catch (\Exception $ex) {
                    // TBD
                }

                $result = array(
                    "error" => "An unknown error occurred while processing the synchronization for this mapping",
                    "additional_errors" => $additionalErrors,
                );

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

                $additionalErrors = array();
                try {
                    $additionalErrors = $service->onSyncException($ex, $this->container);
                    $additionalErrors = (is_array($additionalErrors)) ? $additionalErrors : array();
                } catch (\Exception $ex) {
                    // TBD
                }

                $result = array(
                    "error" => "An unknown error occurred while processing the synchronization for this mapping",
                    "additional_errors" => $additionalErrors,
                );

                $results[$mappingName] = $result;
            }

        }

        return new JsonResponse(array(
            "mappings" => $results
        ));

    }

}
