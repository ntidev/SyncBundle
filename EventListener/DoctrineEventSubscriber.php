<?php

namespace NTI\SyncBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineEventSubscriber implements EventSubscriber
{
    private $container;
    private $syncService;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->syncService = $this->container->get('nti.sync');
    }

    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'preRemove',
        );
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $somethingChanged = false;

        $identityMap = $uow->getIdentityMap();

        // ehhehehehee LOL
        $deletedEntities = count($uow->getScheduledEntityDeletions()) > 0;
        $insertedEntities = count($uow->getScheduledEntityInsertions()) > 0;

        foreach($identityMap as $map) {
            foreach($map as $object) {
                $changes = $uow->getEntityChangeSet($object);
                if(count($changes) > 1 || (count($changes) > 0 && !isset($changes["lastTimestamp"]))) {
                    $somethingChanged = true;
                    break;
                }
            }

            if($somethingChanged) {
                break;
            }
        }

        if($deletedEntities || $insertedEntities)
            $somethingChanged = true;

        foreach ($uow->getScheduledEntityUpdates() as $keyEntity => $entity) {
            $changes = $uow->getEntityChangeSet($entity);

            if(count($changes) == 1 && isset($changes["lastTimestamp"]) && !$somethingChanged) {
                $oid = spl_object_hash($entity);
                $uow->clearEntityChangeSet($oid);
            } else {
                $this->handleEntityChange($em, $entity);
            }
        }

        foreach ($uow->getScheduledEntityInsertions() as $keyEntity => $entity) {
            $this->handleEntityChange($em, $entity);
        }

    }

    public function preRemove(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $class = get_class($entity);
        $id = null;

        if(method_exists($entity, 'getId')) {
            $id = $entity->getId();
        }

        $this->syncService->addToDeleteSyncState($class, $id);
    }

    private function handleEntityChange(EntityManagerInterface $em, $entity) {
        if(method_exists($entity, 'getLastTimestamp')) {
            $timestamp = $entity->getLastTimestamp() ?? time();
        } else {
            $timestamp = time();
        }
        $class = get_class($entity);
        $this->syncService->updateSyncState($em, $class, $timestamp);
    }


}