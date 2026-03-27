<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Entity\Quote;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceService
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceRepository $invoiceRepository,
    ) {}

    /**
     * Generates the next invoice number in the format FAC-YYYY-XXX.
     */
    public function generateNumero(): string
    {
        $year = (int) date('Y');

        $lastNumero = $this->em->createQuery(
            'SELECT i.numero FROM App\Entity\Invoice i
             WHERE i.numero LIKE :pattern
             ORDER BY i.numero DESC'
        )
            ->setParameter('pattern', "FAC-{$year}-%")
            ->setMaxResults(1)
            ->getSingleScalarResult();

        if ($lastNumero) {
            $parts    = explode('-', $lastNumero);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('FAC-%d-%03d', $year, $sequence);
    }

    /**
     * Recalculates HT / TVA / TTC totals from the invoice lines.
     */
    public function calculateTotals(Invoice $invoice): void
    {
        $ht = 0.0;

        foreach ($invoice->getLines() as $line) {
            $line->calculateMontant();
            $ht += (float) $line->getMontantHT();
        }

        $tva = $ht * ((float) $invoice->getTauxTVA() / 100);

        $invoice->setTotalHT(number_format($ht, 2, '.', ''));
        $invoice->setTotalTVA(number_format($tva, 2, '.', ''));
        $invoice->setTotalTTC(number_format($ht + $tva, 2, '.', ''));
    }

    /**
     * Creates a new Invoice from an accepted Quote, copying all lines.
     */
    public function createFromQuote(Quote $quote): Invoice
    {
        $invoice = new Invoice();
        $invoice->setNumero($this->generateNumero());
        $invoice->setClient($quote->getClient());
        $invoice->setProject($quote->getProject());
        $invoice->setQuote($quote);
        $invoice->setObjet($quote->getObjet());
        $invoice->setTauxTVA($quote->getTauxTVA());
        $invoice->setDateEmission(new \DateTime());
        $invoice->setDateEcheance((new \DateTime())->modify('+30 days'));

        foreach ($quote->getLines() as $quoteLine) {
            $invoiceLine = new InvoiceLine();
            $invoiceLine->setDesignation($quoteLine->getDesignation());
            $invoiceLine->setQuantite($quoteLine->getQuantite());
            $invoiceLine->setUnite($quoteLine->getUnite());
            $invoiceLine->setPrixUnitaireHT($quoteLine->getPrixUnitaireHT());
            $invoiceLine->setOrdre($quoteLine->getOrdre());
            $invoiceLine->calculateMontant();
            $invoice->addLine($invoiceLine);
        }

        $this->calculateTotals($invoice);

        return $invoice;
    }

    /**
     * Finds unpaid invoices past their due date and marks them as overdue.
     */
    public function detectRetards(): int
    {
        $invoices = $this->em->createQuery(
            'SELECT i FROM App\Entity\Invoice i
             WHERE i.statut = :enAttente
               AND i.dateEcheance < :now'
        )
            ->setParameter('enAttente', Invoice::STATUS_EN_ATTENTE)
            ->setParameter('now', new \DateTime())
            ->getResult();

        $count = 0;
        foreach ($invoices as $invoice) {
            $invoice->setStatut(Invoice::STATUS_EN_RETARD);
            $count++;
        }

        if ($count > 0) {
            $this->em->flush();
        }

        return $count;
    }
}
