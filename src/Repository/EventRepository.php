<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Event::class);
    }

//    /**
//     * @return Event[] Returns an array of Event objects
//     */

    /**
     * Finds the most recent entry in the 'event' table for the current resource.
     *
     * @todo: Is this query sufficient? Do we care if the last event had an outcome
     * of 'failure'? We probably should specify a digest algorithm as well.
     *
     * @param string $resource_id
     *   The URL of the resource.
     * @param string $hash_algorithm
     *   The hash algorithm (e.g, SHA-1).
     *
     * @return object
     *   A single Event object, or null.
     */
    public function findLastEvent($resource_id, $hash_algorithm)
    {
        $qb = $this->createQueryBuilder('event')
            ->andWhere('event.resource_id = :resource_id')
            ->andWhere('event.hash_algorithm = :hash_algorithm')
            ->setParameter('resource_id', $resource_id)
            ->setParameter('hash_algorithm', $hash_algorithm)
            ->orderBy('event.datestamp', 'DESC')
            ->getQuery();

            $qb->execute();

            $event = $qb->setMaxResults(1)->getOneOrNullResult();
            return $event;
        ;
    }
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
