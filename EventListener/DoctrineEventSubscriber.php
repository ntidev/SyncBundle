<?php

namespace NTI\SyncBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
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
            'postPersist',
            'postUpdate',
            'preRemove',
        );
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if(method_exists($entity, 'getUpdatedOn')) {
            if($entity->getUpdatedOn() != null) {
                $timestamp = $entity->getUpdatedOn()->getTimestamp();
            } else {
                $timestamp = time();
            }
        } else {
            $timestamp = time();
        }

        $class = get_class($args->getEntity());
        $this->syncService->updateSyncState($class, $timestamp);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if(method_exists($entity, 'getUpdatedOn')) {
            if($entity->getUpdatedOn() != null) {
                $timestamp = $entity->getUpdatedOn()->getTimestamp();
            } else {
                $timestamp = time();
            }
        } else {
            $timestamp = time();
        }

        $class = get_class($args->getEntity());
        $this->syncService->updateSyncState($class, $timestamp);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $class = get_class($entity);
        $id = null;

        if(method_exists($entity, 'getId')) {
            $id = $entity->getId();
        }
        $this->syncService->addToDeleteSyncState($class, $id);
    }

}