<?php

namespace App\Repository;

use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 *
 * @method Quote|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quote|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quote[]    findAll()
 * @method Quote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * Returns all quotes matching the given statut, ordered by dateCreation descending.
     *
     * @return Quote[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('q.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Generates the next quote number in the format DEV-YYYY-NNN.
     * Finds the highest existing sequence number for the current year and increments it.
     */
    public function getNextNumero(): string
    {
        $year = (int) date('Y');
        $prefix = sprintf('DEV-%d-', $year);

        $result = $this->createQueryBuilder('q')
            ->select('q.numero')
            ->andWhere('q.numero LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('q.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return sprintf('DEV-%d-%03d', $year, 1);
        }

        $lastNumero = $result['numero'];
        $sequence = (int) substr($lastNumero, strrpos($lastNumero, '-') + 1);

        return sprintf('DEV-%d-%03d', $year, $sequence + 1);
    }
}
