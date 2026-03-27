<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
    ) {}

    #[Route('', name: 'app_event_index', methods: ['GET'])]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $event = new Event();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', 'Événement créé avec succès.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Event $event): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Événement modifié avec succès.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Event $event): Response
    {
        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->em->remove($event);
            $this->em->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_event_index');
    }

    /**
     * JSON endpoint for FullCalendar.
     * Accepts `start` and `end` query parameters (ISO 8601) and returns events as a JSON array.
     */
    #[Route('/api/events', name: 'app_event_api', methods: ['GET'])]
    public function apiEvents(Request $request): JsonResponse
    {
        $startParam = $request->query->get('start');
        $endParam   = $request->query->get('end');

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->orderBy('e.dateDebut', 'ASC');

        if ($startParam) {
            try {
                $start = new \DateTime($startParam);
                $qb->andWhere('e.dateDebut >= :start')->setParameter('start', $start);
            } catch (\Exception) {
                // Ignore invalid date parameter
            }
        }

        if ($endParam) {
            try {
                $end = new \DateTime($endParam);
                $qb->andWhere('e.dateDebut <= :end')->setParameter('end', $end);
            } catch (\Exception) {
                // Ignore invalid date parameter
            }
        }

        /** @var Event[] $events */
        $events = $qb->getQuery()->getResult();

        return $this->json(
            array_map(fn (Event $e) => $e->toFullCalendarArray(), $events)
        );
    }
}
