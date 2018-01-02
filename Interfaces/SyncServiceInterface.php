<?php

namespace NTI\SyncBundle\Interfaces;

/**
 * Interface SyncServiceInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncServiceInterface {

    /**
     * @param $data
     * @return mixed
     */
    public function sync($data);
}
