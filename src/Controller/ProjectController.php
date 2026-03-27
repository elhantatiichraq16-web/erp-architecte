<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectPhase;
use App\Form\ProjectType;
use App\Repository\ProjectPhaseRepository;
use App\Repository\ProjectRepository;
use App\Service\BudgetService;
use App\Service\TimeTrackingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProjectRepository $projectRepository,
        private ProjectPhaseRepository $projectPhaseRepository,
        private TimeTrackingService $timeTrackingService,
        private BudgetService $budgetService,
    ) {}

    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statut   = $request->query->get('statut', '');
        $search   = $request->query->get('search', '');

        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p')
            ->join('p.client', 'c')
            ->orderBy('p.createdAt', 'DESC');

        if ($statut) {
            $qb->andWhere('p.statut = :statut')->setParameter('statut', $statut);
        }

        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.reference LIKE :search OR c.nom LIKE :search')
               ->setParameter('search', "%{$search}%");
        }

        $projects = $qb->getQuery()->getResult();

        return $this->render('project/index.html.twig', [
            'projects'  => $projects,
            'statuts'   => Project::STATUSES,
            'statut'    => $statut,
            'search'    => $search,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $project = new Project();
        $form    = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Auto-generate project reference
            $year  = (int) date('Y');
            $count = $this->em->createQuery(
                'SELECT COUNT(p.id) FROM App\Entity\Project p WHERE p.reference LIKE :pattern'
            )->setParameter('pattern', "PRJ-{$year}-%")->getSingleScalarResult();
            $project->setReference(sprintf('PRJ-%d-%03d', $year, (int) $count + 1));

            $this->em->persist($project);
            $this->em->flush();

            $this->addFlash('success', 'Projet créé avec succès.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Project $project): Response
    {
        $rentabilite = $this->timeTrackingService->getRentabiliteProjet($project);
        $budget      = $this->budgetService->getComparaisonBudget($project);

        return $this->render('project/show.html.twig', [
            'project'     => $project,
            'rentabilite' => $rentabilite,
            'budget'      => $budget,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Project $project): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Projet modifié avec succès.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Project $project): Response
    {
        if ($this->isCsrfTokenValid('delete_project_' . $project->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($project);
                $this->em->flush();
                $this->addFlash('success', 'Projet supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce projet.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_project_index');
    }

    /**
     * AJAX endpoint to update the advancement percentage of a project phase.
     */
    #[Route('/{id}/phases/{phaseId}/update', name: 'app_project_phase_update', methods: ['POST'], requirements: ['id' => '\d+', 'phaseId' => '\d+'])]
    public function updatePhase(Request $request, Project $project, int $phaseId): JsonResponse
    {
        $phase = $this->projectPhaseRepository->find($phaseId);

        if (!$phase || $phase->getProject() !== $project) {
            return $this->json(['error' => 'Phase introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $data       = json_decode($request->getContent(), true);
        $avancement = isset($data['avancement']) ? (int) $data['avancement'] : null;

        if ($avancement === null || $avancement < 0 || $avancement > 100) {
            return $this->json(['error' => 'Valeur d\'avancement invalide (0-100).'], Response::HTTP_BAD_REQUEST);
        }

        $phase->setAvancement($avancement);
        $this->em->flush();

        return $this->json([
            'success'           => true,
            'avancement'        => $phase->getAvancement(),
            'avancementGlobal'  => $project->getAvancementGlobal(),
        ]);
    }
}
