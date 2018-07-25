<?php

namespace NTI\SyncBundle\Interfaces;

use NTI\SyncBundle\Models\SyncPullRequestData;
use NTI\SyncBundle\Models\SyncPullResponseData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface SyncRepositoryInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncRepositoryInterface {

    /**
     * This function should return an instance of SyncPullResponseData containing the results to be sent to the client
     * when a sync is requested. The container is also passed as a parameter in order to give additional
     * flexibility to the repository when making decision on what to show to the client. For example, if the user
     * making the request only has access to a portion of the data, this can be handled via the container in this method
     * of the repository.
     *
     * The resulting structure should be an instance of SyncPullRequestData
     *
     *
     * @param ContainerInterface $container
     * @param SyncPullRequestData $requestData
     * @return SyncPullResponseData
     */
    public function findFromTimestamp(ContainerInterface $container, SyncPullRequestData $requestData);
}
