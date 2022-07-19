<?php

namespace NTI\SyncBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use NTI\SyncBundle\Entity\SyncState;

/**
 * SyncStateRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SyncState::class);
    }

    public function findFromTimestamp($timestamp)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere('s.timestamp >= :timestamp')
            ->setParameter('timestamp', $timestamp)
            ->orderBy('s.timestamp', 'asc')
        ;

        return $qb->getQuery()->getResult();
    }
}
