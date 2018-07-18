<?php

namespace NTI\SyncBundle\Annotations;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class SyncParent
 * @package NTI\SyncBundle\Annotations
 * @Annotation
 */
class SyncParent {
    /**
     * @var string
     * @Required()
     */
    public $getter;
}