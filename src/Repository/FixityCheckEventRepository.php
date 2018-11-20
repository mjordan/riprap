<?php

namespace App\Repository;

use App\Entity\FixityCheckEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FixityCheckEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FixityCheckEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FixityCheckEvent[]    findAll()
 * @method FixityCheckEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FixityCheckEventRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FixityCheckEvent::class);
    }

//    /**
//     * @return FixityCheckEvent[] Returns an array of FixityCheckEvent objects
//     */

    /**
     * Finds the most recent entry in the 'event' table for the current resource.
     *
     * @todo: Is this query sufficient? Do we care if the last event had an outcome
     * of 'failure'? We probably should specify a digest algorithm as well.
     *
     * @param string $resource_id
     *   The URL of the resource.
     * @param string $digest_algorithm
     *   The digest algorithm (e.g, SHA-1).
     *
     * @return object
     *   A single FixityCheckEvent object, or null.
     */
    public function findLastFixityCheckEvent($resource_id, $digest_algorithm)
    {
        $qb = $this->createQueryBuilder('event')
            ->andWhere('event.resource_id = :resource_id')
            ->andWhere('event.digest_algorithm = :digest_algorithm')
            ->setParameter('resource_id', $resource_id)
            ->setParameter('digest_algorithm', $digest_algorithm)
            ->orderBy('event.timestamp', 'DESC')
            ->getQuery();

            $qb->execute();

            $event = $qb->setMaxResults(1)->getOneOrNullResult();
            return $event;
        ;
    }

    /**
     * Finds all entries in the 'event' table for the current resource.
     *
     * @param string $resource_id
     *   The URL of the resource.
     *
     * @return array
     *   A list of FixityCheckEvent objects, or null.
     */
    public function findFixityCheckEvents($resource_id)
    {
        $qb = $this->createQueryBuilder('event')
            ->andWhere('event.resource_id = :resource_id')
            ->setParameter('resource_id', $resource_id)
            ->orderBy('event.timestamp', 'ASC')
            ->getQuery();

            return $qb->execute();
    }

    /**
     * Finds all entries in the 'event' table for the current resource, with URL request parameters.
     *
     * @param string $resource_id
     *   The URL of the resource.
     * @param string|null $timestamp_start
     *   ISO8601 date indicating start of query range.
     * @param string|null $timestamp_end
     *   ISO8601 date indicating end of query range.
     * @param string|null $outcome
     *   The outcome value.       
     *
     * @return array
     *   A list of FixityCheckEvent objects, or null.
     */
    public function findFixityCheckEventsWithParams($resource_id, $timestamp_start, $timestamp_end, $outcome)
    {
        $qb = $this->createQueryBuilder('event')
            ->andWhere('event.resource_id = :resource_id')
            ->setParameter('resource_id', $resource_id);

        if (!is_null($timestamp_start)) {
            $qb->andWhere('event.timestamp >= :timestamp_start')
            ->setParameter('timestamp_start', $timestamp_start);
        }

        if (!is_null($timestamp_end)) {
            $qb->andWhere('event.timestamp <= :timestamp_end')
            ->setParameter('timestamp_end', $timestamp_end);
        }
        
        if (!is_null($outcome)) {
            $qb->andWhere('event.event_outcome = :outcome')
            ->setParameter('outcome', $outcome);
        }

        $qb->orderBy('event.timestamp', 'ASC');
        
        $query = $qb->getQuery();
        return $query->getResult();
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
    public function findOneBySomeField($value): ?FixityCheckEvent
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
