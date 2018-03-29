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

    public function getChangedOids($entities, &$oids) {

        foreach($entities as $entity) {

            $reflection = new \ReflectionObject($entity);
            $methods = $reflection->getMethods();

            foreach($methods as $method) {

                // Getter must not have a parameter!!
                if(strpos($method->getName(), "get") !== false && count($method->getParameters()) <= 0) {
                    $result = $method->invoke($entity);
                    if(is_object($result)) {
                        $reflection2 = new \ReflectionClass(get_class($result));
                        $annotations = $reflection2->getDocComment();
                        if(strpos($annotations, '@ORM\Entity') !== false) {
                            $oid = spl_object_hash($result);
                            if(method_exists($result, 'setLastTimestamp')) {
                                $oids[] = $oid;
                            }
                            $this->getChangedOids(array($result), $oids);
                        }
                    }
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();


        $oids = array();
        $this->getChangedOids($uow->getScheduledEntityUpdates(), $oids);

        foreach ($uow->getScheduledEntityUpdates() as $keyEntity => $entity) {

            $changes = $uow->getEntityChangeSet($entity);

            if (count($changes) == 1 && isset($changes["lastTimestamp"])) {
                $oid = spl_object_hash($entity);
                if(!in_array($oid, $oids)) {
//                    dump(get_class($entity)." is not really chaning anything...");
                    $uow->clearEntityChangeSet($oid);
                    continue;
                }
            }
            $this->handleEntityChange($em, $entity);
        }

        foreach ($uow->getScheduledEntityInsertions() as $keyEntity => $entity) {
            $this->handleEntityChange($em, $entity);
        }
//
//        dump($oids);
//        dump("Finished");
//        die;
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
