<?php

namespace NTI\SyncBundle\Repository;

use Doctrine\ORM\EntityRepository;
use JMS\Serializer\SerializationContext;
use NTI\SyncBundle\Models\SyncPullRequestData;
use NTI\SyncBundle\Models\SyncPullResponseData;
use NTI\SyncBundle\Entity\SyncState;
use NTI\SyncBundle\Interfaces\SyncRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SyncRepository extends EntityRepository  implements SyncRepositoryInterface {

    /**
     * @inheritdoc
     */
    public function findFromTimestamp(ContainerInterface $container, SyncPullRequestData $requestData)
    {
        $timestamp = $requestData->getTimestamp();
        $serializationGroups = $requestData->getSerializationGroups();
        $page = $requestData->getPage() > 0 ? $requestData->getPage() - 1 : 0;
        $limit = $requestData->getLimit();

        // Joins
        $qb = $this->createQueryBuilder('i');
        $qb->andWhere($qb->expr()->gte('i.lastTimestamp', $timestamp));
        $qb->orderBy('i.lastTimestamp', 'asc');

        /**
         * This should be set BEFORE getting the total count, that way the client will receive
         * the actual items that are left for it to sync, not the total amount from a timestamp.
         * @Ref the "page"parameter in SyncPulLRequestData
         */
        $qb->setFirstResult($page * $limit);

        // Total records
        $totalCountQb = clone $qb;
        $totalCountQb->select('COUNT(i.id)');
        $totalCountQuery = $totalCountQb->getQuery();

        try {
            $totalCount = intval($totalCountQuery->getSingleScalarResult());
        } catch (\Exception $e) {
            $totalCount = 0;
        }

        $qb->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        $realLastTimestamp = count($items) <= 0 ? $timestamp : $items[count($items) - 1]->getLastTimestamp();

        $itemsArray = json_decode($container->get('jms_serializer')->serialize($items, 'json', SerializationContext::create()->setGroups($serializationGroups)), true);

        $result = new SyncPullResponseData();
        $result->setData($itemsArray);
        $result->setRealLastTimestamp($realLastTimestamp);
        $result->setTotalCount($totalCount);

        return $result;
    }

}