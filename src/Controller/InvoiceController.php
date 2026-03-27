<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceRepository $invoiceRepository,
        private InvoiceService $invoiceService,
    ) {}

    #[Route('', name: 'app_invoice_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $statut = $request->query->get('statut', '');
        $search = $request->query->get('search', '');

        // Detect and update overdue invoices on each listing page load
        $this->invoiceService->detectRetards();

        $qb = $this->em->createQueryBuilder()
            ->select('i')
            ->from(Invoice::class, 'i')
            ->join('i.client', 'c')
            ->orderBy('i.dateEmission', 'DESC');

        if ($statut) {
            $qb->andWhere('i.statut = :statut')->setParameter('statut', $statut);
        }

        if ($search) {
            $qb->andWhere('i.numero LIKE :search OR i.objet LIKE :search OR c.nom LIKE :search')
               ->setParameter('search', "%{$search}%");
        }

        $invoices = $qb->getQuery()->getResult();

        return $this->render('invoice/index.html.twig', [
            'invoices' => $invoices,
            'statuts'  => Invoice::STATUSES,
            'statut'   => $statut,
            'search'   => $search,
        ]);
    }

    #[Route('/new', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $invoice = new Invoice();
        $form    = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->setNumero($this->invoiceService->generateNumero());
            $this->invoiceService->calculateTotals($invoice);

            $this->em->persist($invoice);
            $this->em->flush();

            $this->addFlash('success', sprintf('Facture %s créée avec succès.', $invoice->getNumero()));

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/new.html.twig', [
            'invoice' => $invoice,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_invoice_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Invoice $invoice): Response
    {
        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoice_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Invoice $invoice): Response
    {
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->invoiceService->calculateTotals($invoice);
            $this->em->flush();

            $this->addFlash('success', sprintf('Facture %s modifiée avec succès.', $invoice->getNumero()));

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_invoice_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Invoice $invoice): Response
    {
        if ($this->isCsrfTokenValid('delete_invoice_' . $invoice->getId(), $request->request->get('_token'))) {
            try {
                $this->em->remove($invoice);
                $this->em->flush();
                $this->addFlash('success', 'Facture supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette facture.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_invoice_index');
    }

    /**
     * Marks the invoice as paid with today's payment date.
     */
    #[Route('/{id}/pay', name: 'app_invoice_pay', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pay(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('pay_invoice_' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($invoice->getStatut() === Invoice::STATUS_PAYEE) {
            $this->addFlash('warning', 'Cette facture est déjà marquée comme payée.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $invoice->setStatut(Invoice::STATUS_PAYEE);
        $invoice->setDatePaiement(new \DateTime());
        $this->em->flush();

        $this->addFlash('success', sprintf('Facture %s marquée comme payée.', $invoice->getNumero()));

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    /**
     * Print-friendly view of the invoice.
     */
    #[Route('/{id}/print', name: 'app_invoice_print', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function print(Invoice $invoice): Response
    {
        return $this->render('invoice/print.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}
