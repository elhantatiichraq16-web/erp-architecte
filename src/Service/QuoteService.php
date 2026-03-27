<?php

namespace App\Service;

use App\Entity\Quote;
use Doctrine\ORM\EntityManagerInterface;

class QuoteService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * Generates the next quote number in the format DEV-YYYY-XXX.
     */
    public function generateNumero(): string
    {
        $year = (int) date('Y');

        $lastNumero = $this->em->createQuery(
            'SELECT q.numero FROM App\Entity\Quote q
             WHERE q.numero LIKE :pattern
             ORDER BY q.numero DESC'
        )
            ->setParameter('pattern', "DEV-{$year}-%")
            ->setMaxResults(1)
            ->getSingleScalarResult();

        if ($lastNumero) {
            $parts    = explode('-', $lastNumero);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('DEV-%d-%03d', $year, $sequence);
    }

    /**
     * Recalculates HT / TVA / TTC totals from the quote lines.
     */
    public function calculateTotals(Quote $quote): void
    {
        $ht = 0.0;

        foreach ($quote->getLines() as $line) {
            $line->calculateMontant();
            $ht += (float) $line->getMontantHT();
        }

        $tva = $ht * ((float) $quote->getTauxTVA() / 100);

        $quote->setTotalHT(number_format($ht, 2, '.', ''));
        $quote->setTotalTVA(number_format($tva, 2, '.', ''));
        $quote->setTotalTTC(number_format($ht + $tva, 2, '.', ''));
    }
}
