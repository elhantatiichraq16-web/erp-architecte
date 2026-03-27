<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Returns all events whose dateDebut falls within the given date range (inclusive).
     * Also includes events that start before $start but end after $start (overlapping).
     *
     * @return Event[]
     */
    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere(
                '(e.dateDebut >= :start AND e.dateDebut <= :end) OR ' .
                '(e.dateFin >= :start AND e.dateFin <= :end) OR ' .
                '(e.dateDebut <= :start AND e.dateFin >= :end)'
            )
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
