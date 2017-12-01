<?php

namespace NTI\SyncBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class SyncController
 * @package NTI\SyncBundle\Controller
 * @Route("/nti/sync")
 */
class SyncController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/", name="nti_sync")
     * @Method("GET|POST")
     */
    public function syncAction(Request $request) {

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
     * @Route("/getSyncStates", name="nti_sync_get_sync_states")
     */
    public function getSyncStatesAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $syncStates = $em->getRepository('NTISyncBundle:SyncState')->findBy(array(), array("mapping" => "asc"));
        $syncStatesArray = json_decode($this->get('serializer')->serialize($syncStates, 'json'), true);
        return new JsonResponse($syncStatesArray, 200);
    }
}
