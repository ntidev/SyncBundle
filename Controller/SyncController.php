<?php

namespace NTI\SyncBundle\Controller;

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
     * @Route("/getSummary", name="nti_sync_get_summary")
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

        $em = $this->getDoctrine()->getManager();

        foreach($mappings as $entry) {

            if(!isset($entry["mapping"])) {

            }

            $mappingName = $entry["mapping"];

            $mapping = $em->getRepository('NTISyncBundle:SyncMapping')->findOneBy(array("name" => $mappingName));

            if(!$mapping) {

            }

            $syncClass = $mapping->getSyncService();

            $service = $this->get($syncClass);

            $service->sync($entry["data"]);

            return new JsonResponse(array("OK"));
        }


    }

}
