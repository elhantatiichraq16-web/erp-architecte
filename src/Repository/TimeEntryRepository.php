<?php

namespace App\Repository;

use App\Entity\Collaborator;
use App\Entity\Project;
use App\Entity\TimeEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeEntry>
 *
 * @method TimeEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeEntry[]    findAll()
 * @method TimeEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeEntry::class);
    }

    /**
     * Returns all time entries for a given project within the specified month/year.
     * $month is 1-12, $year is a 4-digit integer.
     *
     * @return TimeEntry[]
     */
    public function findByProjectAndMonth(Project $project, int $month, int $year): array
    {
        $start = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $end   = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->andWhere('t.date >= :start')
            ->andWhere('t.date <= :end')
            ->setParameter('project', $project)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the total hours logged for a given project.
     */
    public function getTotalHeuresByProject(Project $project): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.heures) AS total')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /**
     * Returns the total hours logged by a given collaborator.
     */
    public function getTotalHeuresByCollaborator(Collaborator $collaborator): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.heures) AS total')
            ->andWhere('t.collaborator = :collaborator')
            ->setParameter('collaborator', $collaborator)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }
}
