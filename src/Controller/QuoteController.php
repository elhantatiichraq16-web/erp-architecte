<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Form\QuoteType;
use App\Repository\QuoteRepository;
use App\Service\InvoiceService;
use App\Service\QuoteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quotes')]
class QuoteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private QuoteRepository $quoteRepository,
        private QuoteService $quoteService,
        private InvoiceService $invoiceService,
    ) {}

    #[Route('', name: 'app_quote_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statut = $request->query->get('statut', '');
        $search = $request->query->get('search', '');

        $qb = $this->em->createQueryBuilder()
            ->select('q')
            ->from(Quote::class, 'q')
            ->join('q.client', 'c')
            ->orderBy('q.dateCreation', 'DESC');

        if ($statut) {
            $qb->andWhere('q.statut = :statut')->setParameter('statut', $statut);
        }

        if ($search) {
            $qb->andWhere('q.numero LIKE :search OR q.objet LIKE :search OR c.nom LIKE :search')
               ->setParameter('search', "%{$search}%");
        }

        $quotes = $qb->getQuery()->getResult();

        return $this->render('quote/index.html.twig', [
            'quotes'  => $quotes,
            'statuts' => Quote::STATUSES,
            'statut'  => $statut,
            'search'  => $search,
        ]);
    }

    #[Route('/new', name: 'app_quote_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $quote = new Quote();
        $form  = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quote->setNumero($this->quoteService->generateNumero());
            $this->quoteService->calculateTotals($quote);

            $this->em->persist($quote);
            $this->em->flush();

            $this->addFlash('success', sprintf('Devis %s créé avec succès.', $quote->getNumero()));

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/new.html.twig', [
            'quote' => $quote,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quote_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Quote $quote): Response
    {
        return $this->render('quote/show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quote_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Quote $quote): Response
    {
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->quoteService->calculateTotals($quote);
            $this->em->flush();

            $this->addFlash('success', sprintf('Devis %s modifié avec succès.', $quote->getNumero()));

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        return $this->render('quote/edit.html.twig', [
            'quote' => $quote,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_quote_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Quote $quote): Response
    {
        if ($this->isCsrfTokenValid('delete_quote_' . $quote->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($quote);
                $this->em->flush();
                $this->addFlash('success', 'Devis supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce devis.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_quote_index');
    }

    /**
     * Converts an accepted quote into a new invoice.
     */
    #[Route('/{id}/convert', name: 'app_quote_convert', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function convert(Request $request, Quote $quote): Response
    {
        if (!$this->isCsrfTokenValid('convert_quote_' . $quote->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        if ($quote->getStatut() !== Quote::STATUS_ACCEPTE) {
            $this->addFlash('warning', 'Seuls les devis acceptés peuvent être convertis en facture.');

            return $this->redirectToRoute('app_quote_show', ['id' => $quote->getId()]);
        }

        $invoice = $this->invoiceService->createFromQuote($quote);
        $this->em->persist($invoice);
        $this->em->flush();

        $this->addFlash('success', sprintf(
            'Devis converti en facture %s avec succès.',
            $invoice->getNumero()
        ));

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }
}
