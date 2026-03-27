<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class BudgetService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectRepository $projectRepository,
    ) {}

    /**
     * Returns budget comparison data for a single project.
     * Includes: planned budget, actual expenses, percentage consumed, and alert flag.
     */
    public function getComparaisonBudget(Project $project): array
    {
        $budgetPrevisionnel = (float) $project->getBudgetPrevisionnel();

        // Sum of all expenses linked to this project
        $depensesReelles = $this->em->createQuery(
            'SELECT SUM(e.montant) FROM App\Entity\Expense e WHERE e.project = :project'
        )
            ->setParameter('project', $project)
            ->getSingleScalarResult() ?? 0;

        $depensesReelles = (float) $depensesReelles;
        $resteADepenser  = $budgetPrevisionnel - $depensesReelles;

        $pourcentage = $budgetPrevisionnel > 0
            ? round(($depensesReelles / $budgetPrevisionnel) * 100, 1)
            : 0;

        // Break down expenses by category
        $parCategorie = $this->em->createQuery(
            'SELECT e.categorie, SUM(e.montant) AS total
             FROM App\Entity\Expense e
             WHERE e.project = :project
             GROUP BY e.categorie
             ORDER BY total DESC'
        )
            ->setParameter('project', $project)
            ->getResult();

        return [
            'project'            => $project,
            'budgetPrevisionnel' => $budgetPrevisionnel,
            'depensesReelles'    => round($depensesReelles, 2),
            'resteADepenser'     => round($resteADepenser, 2),
            'pourcentage'        => $pourcentage,
            'alerte'             => $pourcentage >= 80,
            'depassement'        => $pourcentage >= 100,
            'parCategorie'       => $parCategorie,
        ];
    }

    /**
     * Returns the list of projects that have consumed more than 80% of their budget.
     */
    public function getProjectsEnDepassement(): array
    {
        $projects  = $this->projectRepository->findAll();
        $resultats = [];

        foreach ($projects as $project) {
            $budget = (float) $project->getBudgetPrevisionnel();
            if ($budget <= 0) {
                continue;
            }

            $depenses = $this->em->createQuery(
                'SELECT SUM(e.montant) FROM App\Entity\Expense e WHERE e.project = :project'
            )
                ->setParameter('project', $project)
                ->getSingleScalarResult() ?? 0;

            $depenses    = (float) $depenses;
            $pourcentage = round(($depenses / $budget) * 100, 1);

            if ($pourcentage >= 80) {
                $resultats[] = [
                    'project'    => $project,
                    'budget'     => $budget,
                    'depenses'   => $depenses,
                    'pourcentage' => $pourcentage,
                    'depassement' => $pourcentage >= 100,
                ];
            }
        }

        // Sort by percentage descending
        usort($resultats, fn ($a, $b) => $b['pourcentage'] <=> $a['pourcentage']);

        return $resultats;
    }
}
