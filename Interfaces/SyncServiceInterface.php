<?php

namespace NTI\SyncBundle\Interfaces;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface SyncServiceInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncServiceInterface {
    /**
     * This function should return a plain array containing the results to be sent to the client
     * when a sync is requested. The container is also passed as a parameter in order to give additional
     * flexibility to the repository when making decision on what to show to the client. For example, if the user
     * making the request only has access to a portion of the data, this can be handled via the container in this method
     * of the repository.
     *
     * The resulting structure should be the following:
     *
     * array(
     *      "data" => (array of objects),
     *      SyncState::REAL_LAST_TIMESTAMP => (last updated_on date from the array of objects),
     * )
     *
     *
     * @param $timestamp
     * @param ContainerInterface $container
     * @param array $serializationGroups
     * @return mixed
     */
    public function sync($data);
}
