<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Project;
use App\Repository\EventRepository;
use App\Repository\InvoiceRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimeEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceRepository $invoiceRepository,
        private ProjectRepository $projectRepository,
        private TimeEntryRepository $timeEntryRepository,
        private EventRepository $eventRepository,
    ) {}

    /**
     * Returns main KPI data for the dashboard.
     */
    public function getKpis(): array
    {
        // Sum of paid invoices totalTTC
        $caTotal = $this->em->createQuery(
            'SELECT SUM(i.totalTTC) FROM App\Entity\Invoice i WHERE i.statut = :statut'
        )->setParameter('statut', Invoice::STATUS_PAYEE)->getSingleScalarResult() ?? 0;

        // Count of projects currently in progress
        $projetsEnCours = $this->em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Project p WHERE p.statut = :statut'
        )->setParameter('statut', Project::STATUS_EN_COURS)->getSingleScalarResult() ?? 0;

        // Sum of hours logged in the current calendar month
        $now = new \DateTime();
        $firstDay = new \DateTime($now->format('Y-m-01'));
        $lastDay = new \DateTime($now->format('Y-m-t'));

        $heuresCeMois = $this->em->createQuery(
            'SELECT SUM(t.heures) FROM App\Entity\TimeEntry t WHERE t.date >= :first AND t.date <= :last'
        )
            ->setParameter('first', $firstDay)
            ->setParameter('last', $lastDay)
            ->getSingleScalarResult() ?? 0;

        // Sum of unpaid (non-paid) invoices totalTTC
        $impayes = $this->em->createQuery(
            'SELECT SUM(i.totalTTC) FROM App\Entity\Invoice i WHERE i.statut != :statut'
        )->setParameter('statut', Invoice::STATUS_PAYEE)->getSingleScalarResult() ?? 0;

        return [
            'caTotal'       => (float) $caTotal,
            'projetsEnCours' => (int) $projetsEnCours,
            'heuresCeMois'  => (float) $heuresCeMois,
            'impayes'       => (float) $impayes,
        ];
    }

    /**
     * Returns monthly revenue data for the last 12 months, formatted for Chart.js.
     */
    public function getChiffreAffairesMensuel(): array
    {
        $labels = [];
        $totals = [];
        $now = new \DateTime();

        for ($i = 11; $i >= 0; $i--) {
            $date = (clone $now)->modify("-{$i} months");
            $year  = (int) $date->format('Y');
            $month = (int) $date->format('m');

            $labels[] = $date->format('M Y');

            $startDate = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
            $endDate   = (clone $startDate)->modify('+1 month');

            $total = $this->em->createQuery(
                'SELECT SUM(i.totalTTC)
                 FROM App\Entity\Invoice i
                 WHERE i.statut = :statut
                   AND i.datePaiement >= :startDate
                   AND i.datePaiement < :endDate'
            )
                ->setParameter('statut', Invoice::STATUS_PAYEE)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getSingleScalarResult();

            $totals[] = round((float) $total, 2);
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }

    /**
     * Returns project count per status for a Chart.js donut chart.
     */
    public function getRepartitionProjets(): array
    {
        $results = $this->em->createQuery(
            'SELECT p.statut, COUNT(p.id) as total
             FROM App\Entity\Project p
             GROUP BY p.statut'
        )->getResult();

        $labels = [];
        $data   = [];
        $colors = [
            Project::STATUS_EN_ATTENTE => '#FCD34D',
            Project::STATUS_EN_COURS   => '#3B82F6',
            Project::STATUS_TERMINE    => '#10B981',
            Project::STATUS_SUSPENDU   => '#6B7280',
        ];
        $backgroundColors = [];

        foreach ($results as $row) {
            $statut   = $row['statut'];
            $labels[] = match ($statut) {
                Project::STATUS_EN_ATTENTE => 'En attente',
                Project::STATUS_EN_COURS   => 'En cours',
                Project::STATUS_TERMINE    => 'Terminé',
                Project::STATUS_SUSPENDU   => 'Suspendu',
                default                    => $statut,
            };
            $data[]              = (int) $row['total'];
            $backgroundColors[]  = $colors[$statut] ?? '#6B7280';
        }

        return [
            'labels'           => $labels,
            'data'             => $data,
            'backgroundColors' => $backgroundColors,
        ];
    }

    /**
     * Returns the next 5 upcoming events ordered by start date.
     */
    public function getProchainesEcheances(): array
    {
        return $this->em->createQuery(
            'SELECT e FROM App\Entity\Event e
             WHERE e.dateDebut >= :now
             ORDER BY e.dateDebut ASC'
        )
            ->setParameter('now', new \DateTime())
            ->setMaxResults(5)
            ->getResult();
    }

    /**
     * Returns an array of alerts: overdue invoices and projects over 80% budget.
     */
    public function getAlertes(): array
    {
        $alertes = [];

        // Overdue invoices
        $overdueInvoices = $this->em->createQuery(
            'SELECT i FROM App\Entity\Invoice i
             WHERE i.statut != :statut
               AND i.dateEcheance < :now
             ORDER BY i.dateEcheance ASC'
        )
            ->setParameter('statut', Invoice::STATUS_PAYEE)
            ->setParameter('now', new \DateTime())
            ->getResult();

        foreach ($overdueInvoices as $invoice) {
            $alertes[] = [
                'type'        => 'danger',
                'icon'        => 'bi-exclamation-triangle',
                'title'       => sprintf('Facture %s en retard', $invoice->getNumero()),
                'description' => sprintf(
                    'Client : %s — Échéance : %s',
                    $invoice->getClient()->getDisplayName(),
                    $invoice->getDateEcheance()->format('d/m/Y')
                ),
                'link'        => null,
            ];
        }

        // Projects over 80% of budget
        $projects = $this->projectRepository->findAll();
        foreach ($projects as $project) {
            $budget = (float) $project->getBudgetPrevisionnel();
            if ($budget <= 0) {
                continue;
            }
            $depenses    = $project->getTotalDepenses();
            $pourcentage = ($depenses / $budget) * 100;

            if ($pourcentage >= 80) {
                $alertes[] = [
                    'type'        => $pourcentage >= 100 ? 'danger' : 'warning',
                    'icon'        => 'bi-graph-up-arrow',
                    'title'       => sprintf('Budget projet %s', $project->getNom()),
                    'description' => sprintf(
                        'Consommé à %.1f%% (%s / %s €)',
                        $pourcentage,
                        number_format($depenses, 2, ',', ' '),
                        number_format($budget, 2, ',', ' ')
                    ),
                    'link'        => null,
                ];
            }
        }

        return $alertes;
    }
}
