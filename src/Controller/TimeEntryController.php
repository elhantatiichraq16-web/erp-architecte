<?php

namespace App\Controller;

use App\Entity\TimeEntry;
use App\Form\TimeEntryType;
use App\Repository\CollaboratorRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimeEntryRepository;
use App\Service\TimeTrackingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/time')]
class TimeEntryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TimeEntryRepository $timeEntryRepository,
        private ProjectRepository $projectRepository,
        private CollaboratorRepository $collaboratorRepository,
        private TimeTrackingService $timeTrackingService,
    ) {}

    #[Route('', name: 'app_time_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $projectId      = $request->query->get('project', '');
        $collaboratorId = $request->query->get('collaborator', '');
        $month          = (int) $request->query->get('month', (int) date('m'));
        $year           = (int) $request->query->get('year', (int) date('Y'));

        $startDate = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $endDate   = (clone $startDate)->modify('+1 month');

        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(TimeEntry::class, 't')
            ->join('t.project', 'p')
            ->join('t.collaborator', 'c')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date < :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.date', 'DESC');

        if ($projectId) {
            $qb->andWhere('p.id = :projectId')->setParameter('projectId', (int) $projectId);
        }

        if ($collaboratorId) {
            $qb->andWhere('c.id = :collaboratorId')->setParameter('collaboratorId', (int) $collaboratorId);
        }

        $entries = $qb->getQuery()->getResult();

        $totalHeures = array_reduce(
            $entries,
            fn (float $carry, TimeEntry $e) => $carry + (float) $e->getHeures(),
            0.0
        );

        $heuresParCollaborateur = $this->timeTrackingService->getHeuresParCollaborateur($month, $year);

        return $this->render('time_entry/index.html.twig', [
            'entries'                => $entries,
            'projects'               => $this->projectRepository->findBy([], ['nom' => 'ASC']),
            'collaborators'          => $this->collaboratorRepository->findBy(['actif' => true], ['nom' => 'ASC']),
            'projectId'              => (int) $projectId,
            'collaboratorId'         => (int) $collaboratorId,
            'month'                  => $month,
            'year'                   => $year,
            'totalHeures'            => round($totalHeures, 2),
            'heuresParCollaborateur' => $heuresParCollaborateur,
        ]);
    }

    #[Route('/new', name: 'app_time_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $entry = new TimeEntry();
        $form  = $this->createForm(TimeEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entry);
            $this->em->flush();

            $this->addFlash('success', 'Saisie de temps créée avec succès.');

            return $this->redirectToRoute('app_time_index');
        }

        return $this->render('time_entry/new.html.twig', [
            'entry' => $entry,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_time_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(TimeEntry $entry): Response
    {
        return $this->render('time_entry/show.html.twig', [
            'entry' => $entry,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_time_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, TimeEntry $entry): Response
    {
        $form = $this->createForm(TimeEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Saisie de temps modifiée avec succès.');

            return $this->redirectToRoute('app_time_index');
        }

        return $this->render('time_entry/edit.html.twig', [
            'entry' => $entry,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_time_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, TimeEntry $entry): Response
    {
        if ($this->isCsrfTokenValid('delete_time_' . $entry->getId(), $request->request->get('_token'))) {
            $this->em->remove($entry);
            $this->em->flush();
            $this->addFlash('success', 'Saisie de temps supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_time_index');
    }

    /**
     * Monthly report view with filters by project, collaborator and month.
     */
    #[Route('/rapport', name: 'app_time_rapport', methods: ['GET'])]
    public function rapport(Request $request): Response
    {
        $month = (int) $request->query->get('month', (int) date('m'));
        $year  = (int) $request->query->get('year', (int) date('Y'));

        $heuresParCollaborateur = $this->timeTrackingService->getHeuresParCollaborateur($month, $year);

        $startDate = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $endDate   = (clone $startDate)->modify('+1 month');

        // Per project summary for this month
        $perProjet = $this->em->createQuery(
            'SELECT p.id, p.nom, p.reference, SUM(t.heures) AS totalHeures
             FROM App\Entity\TimeEntry t
             JOIN t.project p
             WHERE t.date >= :startDate AND t.date < :endDate
             GROUP BY p.id, p.nom, p.reference
             ORDER BY totalHeures DESC'
        )
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getResult();

        $totalMois = array_reduce(
            $heuresParCollaborateur,
            fn (float $carry, array $row) => $carry + $row['totalHeures'],
            0.0
        );

        return $this->render('time_entry/rapport.html.twig', [
            'month'                  => $month,
            'year'                   => $year,
            'heuresParCollaborateur' => $heuresParCollaborateur,
            'perProjet'              => $perProjet,
            'totalMois'              => round($totalMois, 2),
        ]);
    }
}
