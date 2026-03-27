<?php

namespace App\Repository;

use App\Entity\Expense;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 *
 * @method Expense|null find($id, $lockMode = null, $lockVersion = null)
 * @method Expense|null findOneBy(array $criteria, array $orderBy = null)
 * @method Expense[]    findAll()
 * @method Expense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    /**
     * Returns all expenses for a given project, ordered by date descending.
     *
     * @return Expense[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.project = :project')
            ->setParameter('project', $project)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the sum of montant for all expenses linked to a given project.
     */
    public function getTotalByProject(Project $project): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.montant) AS total')
            ->andWhere('e.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }
}
