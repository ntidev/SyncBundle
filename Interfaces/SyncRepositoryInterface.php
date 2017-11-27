<?php

namespace NTI\SyncBundle\Interfaces;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface SyncRepositoryInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncRepositoryInterface {
    /**
     * This function should return a plain array containing the results to be sent to the client
     * when a sync is requested. The container is also passed as a parameter in order to give additional
     * flexibility to the repository when making decision on what to show to the client. For example, if the user
     * making the request only has access to a portion of the data, this can be handled via the container in this method
     * of the repository.
     *
     * @param $timestamp
     * @param ContainerInterface $container
     * @return mixed
     */
    public function findFromTimestamp($timestamp, ContainerInterface $container);
}