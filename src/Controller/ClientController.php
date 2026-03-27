<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients')]
class ClientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClientRepository $clientRepository,
    ) {}

    #[Route('', name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search  = $request->query->get('search', '');
        $clients = $search
            ? $this->em->createQuery(
                'SELECT c FROM App\Entity\Client c
                 WHERE c.nom LIKE :q OR c.prenom LIKE :q OR c.societe LIKE :q OR c.email LIKE :q
                 ORDER BY c.nom ASC'
            )->setParameter('q', "%{$search}%")->getResult()
            : $this->clientRepository->findBy([], ['nom' => 'ASC']);

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'search'  => $search,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $client = new Client();
        $form   = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($client);
            $this->em->flush();

            $this->addFlash('success', 'Client créé avec succès.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Client modifié avec succès.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Client $client): Response
    {
        if ($this->isCsrfTokenValid('delete_client_' . $client->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($client);
                $this->em->flush();
                $this->addFlash('success', 'Client supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce client car il possède des projets, devis ou factures liés.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_client_index');
    }
}
