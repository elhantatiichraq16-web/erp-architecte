<?php

namespace App\Repository;

use App\Entity\ProjectPhase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectPhase>
 *
 * @method ProjectPhase|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectPhase|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectPhase[]    findAll()
 * @method ProjectPhase[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectPhaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectPhase::class);
    }
}
