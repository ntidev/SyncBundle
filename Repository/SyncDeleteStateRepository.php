<?php

namespace NTI\SyncBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use NTI\SyncBundle\Entity\SyncDeleteState;

/**
 * SyncDeleteStateRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SyncDeleteStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SyncDeleteState::class);
    }

    public function findFromTimestamp($mappingName, $timestamp)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->innerJoin('s.mapping', 'm')
            ->andWhere('m.name = :mappingName')
            ->setParameter('mappingName', $mappingName)
            ->andWhere('s.timestamp >= :timestamp')
            ->setParameter('timestamp', $timestamp)
            ->orderBy('s.timestamp', 'asc')
        ;

        return $qb->getQuery()->getResult();
    }
}
