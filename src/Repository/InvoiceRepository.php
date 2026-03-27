<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 *
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Returns all invoices matching the given statut, ordered by dateEmission descending.
     *
     * @return Invoice[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('i.dateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all unpaid invoices whose dateEcheance is in the past (en retard).
     *
     * @return Invoice[]
     */
    public function findEnRetard(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.statut != :payee')
            ->andWhere('i.dateEcheance < :today')
            ->setParameter('payee', Invoice::STATUS_PAYEE)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('i.dateEcheance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Generates the next invoice number in the format FACT-YYYY-NNN.
     * Finds the highest existing sequence number for the current year and increments it.
     */
    public function getNextNumero(): string
    {
        $year = (int) date('Y');
        $prefix = sprintf('FACT-%d-', $year);

        $result = $this->createQueryBuilder('i')
            ->select('i.numero')
            ->andWhere('i.numero LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('i.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return sprintf('FACT-%d-%03d', $year, 1);
        }

        $lastNumero = $result['numero'];
        $sequence = (int) substr($lastNumero, strrpos($lastNumero, '-') + 1);

        return sprintf('FACT-%d-%03d', $year, $sequence + 1);
    }

    /**
     * Returns the sum of totalTTC for invoices grouped by statut.
     * Returns an associative array [statut => totalTTC].
     *
     * @return array<string, float>
     */
    public function getTotalByStatut(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.statut, SUM(i.totalTTC) AS totalTTC')
            ->groupBy('i.statut')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['statut']] = (float) $row['totalTTC'];
        }

        return $result;
    }
}
