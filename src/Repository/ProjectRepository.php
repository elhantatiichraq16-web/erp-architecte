<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Returns all projects matching the given statut, ordered by dateDebut descending.
     *
     * @return Project[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('p.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the number of projects for each statut as an associative array [statut => count].
     *
     * @return array<string, int>
     */
    public function countByStatut(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.statut, COUNT(p.id) AS total')
            ->groupBy('p.statut')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['statut']] = (int) $row['total'];
        }

        return $result;
    }
}
