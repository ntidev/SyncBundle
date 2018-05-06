<?php

namespace NTI\SyncBundle\EventListener;

use AppBundle\Entity\Empanada\Empanada;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use NTI\SyncBundle\Annotations\SyncParent;
use NTI\SyncBundle\Interfaces\SyncEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineEventSubscriber implements EventSubscriber
{
    private $container;
    private $syncService;

    public function __construct(ContainerInterface $container)
    {
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

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            // Check if the entity should be synchronized
            if (!($entity instanceof SyncEntityInterface)) {
                continue;
            }
            $this->processEntity($em, $entity);
        }

    }

    public function preRemove(LifecycleEventArgs $args)
    {

    }

    private function processEntity(EntityManagerInterface $em, $entity)
    {
        $uow = $em->getUnitOfWork();

        $timestamp = time();

        // Check if this class itself has a lastTimestamp
        if(method_exists($entity, 'setLastTimestamp')) {
            $entity->setLastTimestamp($timestamp);
            $uow->computeChangeSet($em->getClassMetadata(get_class($entity)), $entity);
        }

        // Check if there are any relationships that should notified
        $annotationReader = new AnnotationReader();
        $reflection = new \ReflectionClass(get_class($entity));

        /** @var \ReflectionProperty $property */
        foreach ($reflection->getProperties() as $property) {
            /** @var SyncParent $annotation */
            if (null !== ($annotation = $annotationReader->getPropertyAnnotation($property, SyncParent::class))) {
                $getter = $annotation->getter;
                $parent = $entity->$getter();
                if($parent instanceof SyncEntityInterface) {
                    $this->processEntity($em, $parent);
                }
            }
        }
    }
}
