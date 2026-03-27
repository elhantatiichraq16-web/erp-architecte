<?php

namespace App\Service;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class TimeTrackingService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * Returns profitability data for a project:
     * planned hours (sum from phases, estimated), actual hours, cost.
     */
    public function getRentabiliteProjet(Project $project): array
    {
        // Actual hours logged on this project
        $heuresReelles = $this->em->createQuery(
            'SELECT SUM(t.heures) FROM App\Entity\TimeEntry t WHERE t.project = :project'
        )
            ->setParameter('project', $project)
            ->getSingleScalarResult() ?? 0;

        // Cost: sum of hours * collaborator hourly rate
        $coutReel = $this->em->createQuery(
            'SELECT SUM(t.heures * c.tauxHoraire)
             FROM App\Entity\TimeEntry t
             JOIN t.collaborator c
             WHERE t.project = :project'
        )
            ->setParameter('project', $project)
            ->getSingleScalarResult() ?? 0;

        // Budget prévisionnel as planned cost reference
        $budgetPrevisionnel = (float) $project->getBudgetPrevisionnel();

        // Estimated planned hours: budget / average hourly rate (fallback: raw budget)
        $avgTauxHoraire = $this->em->createQuery(
            'SELECT AVG(c.tauxHoraire) FROM App\Entity\Collaborator c WHERE c.actif = true'
        )->getSingleScalarResult() ?? 0;

        $heuresPrevues = ($avgTauxHoraire > 0) ? ($budgetPrevisionnel / (float) $avgTauxHoraire) : 0;

        $heuresReelles      = (float) $heuresReelles;
        $coutReel           = (float) $coutReel;
        $ecartHeures        = $heuresReelles - $heuresPrevues;
        $ecartCout          = $coutReel - $budgetPrevisionnel;
        $pourcentageBudget  = $budgetPrevisionnel > 0
            ? round(($coutReel / $budgetPrevisionnel) * 100, 1)
            : 0;

        return [
            'project'            => $project,
            'heuresPrevues'      => round($heuresPrevues, 2),
            'heuresReelles'      => round($heuresReelles, 2),
            'ecartHeures'        => round($ecartHeures, 2),
            'budgetPrevisionnel' => $budgetPrevisionnel,
            'coutReel'           => round($coutReel, 2),
            'ecartCout'          => round($ecartCout, 2),
            'pourcentageBudget'  => $pourcentageBudget,
            'alerteBudget'       => $pourcentageBudget >= 80,
        ];
    }

    /**
     * Returns hours worked per collaborator for the given month and year.
     */
    public function getHeuresParCollaborateur(int $month, int $year): array
    {
        $startDate = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $endDate   = (clone $startDate)->modify('+1 month');

        $results = $this->em->createQuery(
            'SELECT c.id, c.prenom, c.nom, c.couleur, SUM(t.heures) AS totalHeures
             FROM App\Entity\TimeEntry t
             JOIN t.collaborator c
             WHERE t.date >= :startDate AND t.date < :endDate
             GROUP BY c.id, c.prenom, c.nom, c.couleur
             ORDER BY totalHeures DESC'
        )
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getResult();

        return array_map(function (array $row) {
            return [
                'id'          => $row['id'],
                'nom'         => $row['prenom'] . ' ' . $row['nom'],
                'couleur'     => $row['couleur'] ?? '#3B82F6',
                'totalHeures' => round((float) $row['totalHeures'], 2),
            ];
        }, $results);
    }
}
