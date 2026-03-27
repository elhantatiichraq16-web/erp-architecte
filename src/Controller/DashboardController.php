<?php

namespace App\Controller;

use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $kpis              = $this->dashboardService->getKpis();
        $caData            = $this->dashboardService->getChiffreAffairesMensuel();
        $repartitionData   = $this->dashboardService->getRepartitionProjets();
        $echeances         = $this->dashboardService->getProchainesEcheances();
        $alertes           = $this->dashboardService->getAlertes();

        return $this->render('dashboard/index.html.twig', [
            'kpis'       => $kpis,
            'chartData'  => [
                'labels'    => $caData['labels'],
                'values'    => $caData['totals'],
                'objective' => 0,
            ],
            'repartition' => [
                'labels' => $repartitionData['labels'],
                'values' => $repartitionData['data'],
                'colors' => $repartitionData['backgroundColors'],
            ],
            'echeances'  => $echeances,
            'alertes'    => $alertes,
        ]);
    }
}
