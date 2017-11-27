<?php

namespace NTI\SyncBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
     */
    public function syncAction(Request $request) {

        $timestamp = ($request->get('timestamp')) ? intval($request->get('timestamp')) : 0;

        $changes = $this->get('nti.sync')->getFromTimestamp($timestamp);

        return new JsonResponse($changes, 200);
    }
}
