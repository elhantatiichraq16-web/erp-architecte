<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    private const HEADER_BG = '1E3A5F';
    private const HEADER_FONT = 'FFFFFF';
    private const ALT_ROW_BG = 'F0F4F8';
    private const BORDER_COLOR = 'D1D5DB';
    private const TOTAL_BG = 'EBF5FF';
    private const MONEY_FORMAT = '#,##0.00 €';
    private const DATE_FORMAT = 'DD/MM/YYYY';

    #[Route('/export/excel', name: 'app_export_excel')]
    public function exportExcel(EntityManagerInterface $em): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('ERP Architecte')
            ->setTitle('Export complet — ERP Architecte')
            ->setDescription('Export de toutes les données de l\'application ERP Architecte');

        $spreadsheet->removeSheetByIndex(0);

        $this->buildClientsSheet($spreadsheet, $em);
        $this->buildProjectsSheet($spreadsheet, $em);
        $this->buildQuotesSheet($spreadsheet, $em);
        $this->buildQuoteLinesSheet($spreadsheet, $em);
        $this->buildInvoicesSheet($spreadsheet, $em);
        $this->buildInvoiceLinesSheet($spreadsheet, $em);
        $this->buildExpensesSheet($spreadsheet, $em);
        $this->buildTimeEntriesSheet($spreadsheet, $em);
        $this->buildEventsSheet($spreadsheet, $em);
        $this->buildDocumentsSheet($spreadsheet, $em);
        $this->buildCollaboratorsSheet($spreadsheet, $em);

        $spreadsheet->setActiveSheetIndex(0);

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $filename = 'ERP_Architecte_Export_' . date('Y-m-d_His') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function buildClientsSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Clients');

        $headers = ['ID', 'Nom', 'Prénom', 'Société', 'E-mail', 'Téléphone', 'Adresse', 'Code postal', 'Ville', 'SIRET', 'Nb Projets', 'Nb Devis', 'Nb Factures', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $clients = $em->getRepository(\App\Entity\Client::class)->findAll();
        $row = 2;
        foreach ($clients as $client) {
            $sheet->fromArray([
                $client->getId(),
                $client->getNom(),
                $client->getPrenom(),
                $client->getSociete(),
                $client->getEmail(),
                $client->getTelephone(),
                $client->getAdresse(),
                $client->getCodePostal(),
                $client->getVille(),
                $client->getSiret(),
                $client->getProjects()->count(),
                $client->getQuotes()->count(),
                $client->getInvoices()->count(),
                $client->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildProjectsSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Projets');

        $headers = ['ID', 'Référence', 'Nom', 'Client', 'Statut', 'Adresse chantier', 'Surface (m²)', 'Honoraires (€)', 'Budget prév. (€)', 'Date début', 'Date fin prév.', 'Nb Collaborateurs', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $projects = $em->getRepository(\App\Entity\Project::class)->findAll();
        $row = 2;
        foreach ($projects as $project) {
            $sheet->fromArray([
                $project->getId(),
                $project->getReference(),
                $project->getNom(),
                $project->getClient()?->getNom() . ' ' . $project->getClient()?->getPrenom(),
                $this->translateStatut($project->getStatut()),
                $project->getAdresseChantier(),
                $project->getSurface(),
                $project->getMontantHonoraires(),
                $project->getBudgetPrevisionnel(),
                $project->getDateDebut()?->format('d/m/Y'),
                $project->getDateFinPrevisionnelle()?->format('d/m/Y'),
                $project->getCollaborators()->count(),
                $project->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'H' . $row);
            $this->formatMoney($sheet, 'I' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildQuotesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Devis');

        $headers = ['ID', 'Numéro', 'Client', 'Projet', 'Objet', 'Date création', 'Date validité', 'Statut', 'Total HT (€)', 'TVA (€)', 'Total TTC (€)', 'Taux TVA (%)'];
        $this->writeHeaders($sheet, $headers);

        $quotes = $em->getRepository(\App\Entity\Quote::class)->findAll();
        $row = 2;
        foreach ($quotes as $quote) {
            $sheet->fromArray([
                $quote->getId(),
                $quote->getNumero(),
                $quote->getClient()?->getNom(),
                $quote->getProject()?->getReference(),
                $quote->getObjet(),
                $quote->getDateCreation()?->format('d/m/Y'),
                $quote->getDateValidite()?->format('d/m/Y'),
                $this->translateStatut($quote->getStatut()),
                $quote->getTotalHT(),
                $quote->getTotalTVA(),
                $quote->getTotalTTC(),
                $quote->getTauxTVA(),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'I' . $row);
            $this->formatMoney($sheet, 'J' . $row);
            $this->formatMoney($sheet, 'K' . $row);
            $row++;
        }

        $this->addTotalRow($sheet, $row, ['I', 'J', 'K'], count($headers));
        $this->styleSheet($sheet, count($headers), $row);
    }

    private function buildQuoteLinesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Lignes de devis');

        $headers = ['ID', 'N° Devis', 'Désignation', 'Quantité', 'Unité', 'Prix unit. HT (€)', 'Montant HT (€)', 'Ordre'];
        $this->writeHeaders($sheet, $headers);

        $lines = $em->getRepository(\App\Entity\QuoteLine::class)->findAll();
        $row = 2;
        foreach ($lines as $line) {
            $sheet->fromArray([
                $line->getId(),
                $line->getQuote()?->getNumero(),
                $line->getDesignation(),
                $line->getQuantite(),
                $line->getUnite(),
                $line->getPrixUnitaireHT(),
                $line->getMontantHT(),
                $line->getOrdre(),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'F' . $row);
            $this->formatMoney($sheet, 'G' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildInvoicesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Factures');

        $headers = ['ID', 'Numéro', 'Client', 'Projet', 'Devis lié', 'Objet', 'Date émission', 'Date échéance', 'Statut', 'Date paiement', 'Total HT (€)', 'TVA (€)', 'Total TTC (€)', 'Taux TVA (%)'];
        $this->writeHeaders($sheet, $headers);

        $invoices = $em->getRepository(\App\Entity\Invoice::class)->findAll();
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->fromArray([
                $invoice->getId(),
                $invoice->getNumero(),
                $invoice->getClient()?->getNom(),
                $invoice->getProject()?->getReference(),
                $invoice->getQuote()?->getNumero(),
                $invoice->getObjet(),
                $invoice->getDateEmission()?->format('d/m/Y'),
                $invoice->getDateEcheance()?->format('d/m/Y'),
                $this->translateStatut($invoice->getStatut()),
                $invoice->getDatePaiement()?->format('d/m/Y'),
                $invoice->getTotalHT(),
                $invoice->getTotalTVA(),
                $invoice->getTotalTTC(),
                $invoice->getTauxTVA(),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'K' . $row);
            $this->formatMoney($sheet, 'L' . $row);
            $this->formatMoney($sheet, 'M' . $row);
            $row++;
        }

        $this->addTotalRow($sheet, $row, ['K', 'L', 'M'], count($headers));
        $this->styleSheet($sheet, count($headers), $row);
    }

    private function buildInvoiceLinesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Lignes de factures');

        $headers = ['ID', 'N° Facture', 'Désignation', 'Quantité', 'Unité', 'Prix unit. HT (€)', 'Montant HT (€)', 'Ordre'];
        $this->writeHeaders($sheet, $headers);

        $lines = $em->getRepository(\App\Entity\InvoiceLine::class)->findAll();
        $row = 2;
        foreach ($lines as $line) {
            $sheet->fromArray([
                $line->getId(),
                $line->getInvoice()?->getNumero(),
                $line->getDesignation(),
                $line->getQuantite(),
                $line->getUnite(),
                $line->getPrixUnitaireHT(),
                $line->getMontantHT(),
                $line->getOrdre(),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'F' . $row);
            $this->formatMoney($sheet, 'G' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildExpensesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Dépenses');

        $headers = ['ID', 'Projet', 'Date', 'Catégorie', 'Montant (€)', 'Description', 'Fournisseur', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $expenses = $em->getRepository(\App\Entity\Expense::class)->findAll();
        $row = 2;
        foreach ($expenses as $expense) {
            $sheet->fromArray([
                $expense->getId(),
                $expense->getProject()?->getReference(),
                $expense->getDate()?->format('d/m/Y'),
                $expense->getCategorie(),
                $expense->getMontant(),
                $expense->getDescription(),
                $expense->getFournisseur(),
                $expense->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'E' . $row);
            $row++;
        }

        $this->addTotalRow($sheet, $row, ['E'], count($headers));
        $this->styleSheet($sheet, count($headers), $row);
    }

    private function buildTimeEntriesSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Temps passé');

        $headers = ['ID', 'Collaborateur', 'Projet', 'Phase', 'Date', 'Heures', 'Description', 'Facturable', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $entries = $em->getRepository(\App\Entity\TimeEntry::class)->findAll();
        $row = 2;
        foreach ($entries as $entry) {
            $collab = $entry->getCollaborator();
            $sheet->fromArray([
                $entry->getId(),
                $collab ? $collab->getPrenom() . ' ' . $collab->getNom() : '',
                $entry->getProject()?->getReference(),
                $entry->getPhase(),
                $entry->getDate()?->format('d/m/Y'),
                $entry->getHeures(),
                $entry->getDescription(),
                $entry->isFacturable() ? 'Oui' : 'Non',
                $entry->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);
            $row++;
        }

        $this->addTotalRow($sheet, $row, ['F'], count($headers));
        $this->styleSheet($sheet, count($headers), $row);
    }

    private function buildEventsSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Événements');

        $headers = ['ID', 'Titre', 'Type', 'Projet', 'Date début', 'Date fin', 'Journée entière', 'Lieu', 'Description'];
        $this->writeHeaders($sheet, $headers);

        $events = $em->getRepository(\App\Entity\Event::class)->findAll();
        $row = 2;
        foreach ($events as $event) {
            $sheet->fromArray([
                $event->getId(),
                $event->getTitre(),
                $event->getType(),
                $event->getProject()?->getReference(),
                $event->getDateDebut()?->format('d/m/Y H:i'),
                $event->getDateFin()?->format('d/m/Y H:i'),
                $event->isAllDay() ? 'Oui' : 'Non',
                $event->getLieu(),
                $event->getDescription(),
            ], null, 'A' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildDocumentsSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Documents');

        $headers = ['ID', 'Projet', 'Nom', 'Fichier', 'Catégorie', 'Version', 'Notes', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $documents = $em->getRepository(\App\Entity\Document::class)->findAll();
        $row = 2;
        foreach ($documents as $doc) {
            $sheet->fromArray([
                $doc->getId(),
                $doc->getProject()?->getReference(),
                $doc->getNom(),
                $doc->getNomFichier(),
                $doc->getCategorie(),
                $doc->getVersion(),
                $doc->getNotes(),
                $doc->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    private function buildCollaboratorsSheet(Spreadsheet $spreadsheet, EntityManagerInterface $em): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Collaborateurs');

        $headers = ['ID', 'Nom', 'Prénom', 'E-mail', 'Rôle', 'Taux horaire (€)', 'Téléphone', 'Actif', 'Nb Projets', 'Créé le'];
        $this->writeHeaders($sheet, $headers);

        $collaborators = $em->getRepository(\App\Entity\Collaborator::class)->findAll();
        $row = 2;
        foreach ($collaborators as $collab) {
            $sheet->fromArray([
                $collab->getId(),
                $collab->getNom(),
                $collab->getPrenom(),
                $collab->getEmail(),
                $collab->getRole(),
                $collab->getTauxHoraire(),
                $collab->getTelephone(),
                $collab->isActif() ? 'Oui' : 'Non',
                $collab->getProjects()->count(),
                $collab->getCreatedAt()?->format('d/m/Y'),
            ], null, 'A' . $row);

            $this->formatMoney($sheet, 'F' . $row);
            $row++;
        }

        $this->styleSheet($sheet, count($headers), $row - 1);
    }

    // ─── Styling helpers ───────────────────────────────────

    private function writeHeaders($sheet, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    }

    private function styleSheet($sheet, int $colCount, int $lastRow): void
    {
        $lastCol = chr(64 + $colCount);
        $headerRange = 'A1:' . $lastCol . '1';

        // Header style
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => self::HEADER_FONT],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Alternate row colors
        for ($r = 2; $r <= $lastRow; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(self::ALT_ROW_BG);
            }
        }

        // Borders for all data
        if ($lastRow >= 1) {
            $sheet->getStyle('A1:' . $lastCol . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::BORDER_COLOR],
                    ],
                ],
            ]);
        }

        // Auto-size columns
        for ($i = 0; $i < $colCount; $i++) {
            $sheet->getColumnDimension(chr(65 + $i))->setAutoSize(true);
        }

        // Freeze header
        $sheet->freezePane('A2');

        // Auto-filter
        if ($lastRow >= 2) {
            $sheet->setAutoFilter('A1:' . $lastCol . $lastRow);
        }
    }

    private function formatMoney($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
    }

    private function addTotalRow($sheet, int $row, array $sumCols, int $colCount): void
    {
        $lastCol = chr(64 + $colCount);
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        foreach ($sumCols as $col) {
            $sheet->setCellValue($col . $row, '=SUM(' . $col . '2:' . $col . ($row - 1) . ')');
            $this->formatMoney($sheet, $col . $row);
        }

        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::TOTAL_BG],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::HEADER_BG]],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::HEADER_BG]],
            ],
        ]);
    }

    private function translateStatut(string $statut): string
    {
        return match ($statut) {
            'en_attente' => 'En attente',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            'brouillon' => 'Brouillon',
            'envoye' => 'Envoyé',
            'accepte' => 'Accepté',
            'refuse' => 'Refusé',
            'payee' => 'Payée',
            'en_retard' => 'En retard',
            default => $statut,
        };
    }
}
