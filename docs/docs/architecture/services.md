---
id: services
title: Services Métier
sidebar_label: Services
description: Documentation des 5 services métier Symfony de l'ERP Architecte.
---

# Services Métier

L'ERP Architecte centralise la logique métier dans **5 services Symfony** injectables. Chaque service est responsable d'un domaine fonctionnel précis et est utilisé par les contrôleurs via l'injection de dépendances automatique.

---

## Vue d'ensemble

| Service | Namespace | Responsabilité principale |
|---------|-----------|--------------------------|
| `InvoiceService` | `App\Service` | Génération des factures et des PDFs |
| `QuoteService` | `App\Service` | Gestion des devis et conversion en facture |
| `TimeTrackingService` | `App\Service` | Calculs de temps et rapports |
| `ProjectStatsService` | `App\Service` | Statistiques et KPIs projet |
| `NotificationService` | `App\Service` | Envoi d'emails et notifications |

---

## 1. InvoiceService

**Fichier :** `src/Service/InvoiceService.php`

Gère le cycle de vie complet des factures : création, numérotation automatique, calculs de TVA, génération PDF et suivi des paiements.

### Injection de dépendances

```php
class InvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository,
        private Pdf $pdf,                    // DomPDF
        private TwigEnvironment $twig,
        private string $projectDir,
        private string $defaultVatRate,
    ) {}
}
```

### Méthodes

#### `createFromQuote(Quote $quote): Invoice`

Convertit un devis accepté en facture. Copie toutes les lignes de prestation, hérite du client et du projet.

```php
public function createFromQuote(Quote $quote): Invoice
{
    $invoice = new Invoice();
    $invoice->setClient($quote->getClient());
    $invoice->setProject($quote->getProject());
    $invoice->setNumber($this->generateNumber());
    $invoice->setStatus(InvoiceStatus::Draft);
    $invoice->setIssueDate(new \DateTimeImmutable());
    $invoice->setDueDate((new \DateTimeImmutable())->modify('+30 days'));

    foreach ($quote->getItems() as $quoteItem) {
        $item = new InvoiceItem();
        $item->setDescription($quoteItem->getDescription());
        $item->setQuantity($quoteItem->getQuantity());
        $item->setUnitPrice($quoteItem->getUnitPrice());
        $item->setTotal($quoteItem->getTotal());
        $invoice->addItem($item);
    }

    $this->recalculate($invoice);
    $this->entityManager->persist($invoice);
    $this->entityManager->flush();

    return $invoice;
}
```

#### `generateNumber(): string`

Génère un numéro de facture séquentiel et unique au format `FAC-YYYY-NNN`.

```php
public function generateNumber(): string
{
    $year = (new \DateTimeImmutable())->format('Y');
    $lastInvoice = $this->invoiceRepository->findLastOfYear($year);
    $sequence = $lastInvoice ? (int)substr($lastInvoice->getNumber(), -3) + 1 : 1;

    return sprintf('FAC-%s-%03d', $year, $sequence);
}
```

#### `recalculate(Invoice $invoice): void`

Recalcule le sous-total HT, le montant TVA et le total TTC en fonction des lignes.

```php
public function recalculate(Invoice $invoice): void
{
    $subtotal = array_reduce(
        $invoice->getItems()->toArray(),
        fn(float $carry, InvoiceItem $item) => $carry + $item->getTotal(),
        0.0
    );

    $vatRate = $invoice->getVatRate() ?? (float)$this->defaultVatRate;
    $vatAmount = round($subtotal * $vatRate / 100, 2);

    $invoice->setSubtotal($subtotal);
    $invoice->setVatAmount($vatAmount);
    $invoice->setTotal($subtotal + $vatAmount);
}
```

#### `generatePdf(Invoice $invoice): string`

Génère le PDF de la facture et retourne le chemin du fichier.

```php
public function generatePdf(Invoice $invoice): string
{
    $html = $this->twig->render('invoice/pdf.html.twig', [
        'invoice' => $invoice,
    ]);

    $this->pdf->setOption('defaultFont', 'Arial');
    $this->pdf->setOption('isHtml5ParserEnabled', true);
    $this->pdf->getpdf($html);

    $filename = sprintf('%s/var/invoices/%s.pdf', $this->projectDir, $invoice->getNumber());
    $this->pdf->save($filename);

    return $filename;
}
```

#### `markAsPaid(Invoice $invoice, \DateTimeInterface $paidAt = null): void`

Marque une facture comme payée.

```php
public function markAsPaid(Invoice $invoice, ?\DateTimeInterface $paidAt = null): void
{
    $invoice->setStatus(InvoiceStatus::Paid);
    $invoice->setPaidAt($paidAt ?? new \DateTime());
    $this->entityManager->flush();
}
```

#### `getOverdueInvoices(): array`

Retourne toutes les factures en retard de paiement.

---

## 2. QuoteService

**Fichier :** `src/Service/QuoteService.php`

Gère la création, le calcul et la gestion du cycle de vie des devis.

### Méthodes

#### `generateNumber(): string`

Génère un numéro séquentiel au format `DEV-YYYY-NNN`.

#### `recalculate(Quote $quote): void`

Même logique que `InvoiceService::recalculate()` mais pour les devis.

#### `createFromTemplate(array $templateData): Quote`

Crée un devis pré-rempli à partir d'un modèle (prestations standards d'un type de mission).

```php
public function createFromTemplate(array $templateData, Client $client): Quote
{
    $quote = new Quote();
    $quote->setClient($client);
    $quote->setNumber($this->generateNumber());
    $quote->setStatus(QuoteStatus::Draft);
    $quote->setIssueDate(new \DateTimeImmutable());
    $quote->setValidUntil((new \DateTimeImmutable())->modify('+30 days'));

    foreach ($templateData['items'] as $position => $itemData) {
        $item = new QuoteItem();
        $item->setDescription($itemData['description']);
        $item->setQuantity($itemData['quantity']);
        $item->setUnitPrice($itemData['unit_price']);
        $item->setTotal($itemData['quantity'] * $itemData['unit_price']);
        $item->setPosition($position + 1);
        $quote->addItem($item);
    }

    $this->recalculate($quote);
    return $quote;
}
```

#### `accept(Quote $quote): Invoice`

Change le statut du devis en `accepted` et crée la facture correspondante via `InvoiceService`.

#### `reject(Quote $quote, string $reason = null): void`

Change le statut du devis en `rejected` et enregistre la raison optionnelle.

#### `generatePdf(Quote $quote): string`

Génère le PDF du devis.

#### `duplicate(Quote $quote): Quote`

Crée une copie du devis avec un nouveau numéro et le statut `draft`.

---

## 3. TimeTrackingService

**Fichier :** `src/Service/TimeTrackingService.php`

Centralise les calculs liés au suivi du temps : totaux, rapports, comparaisons budget/réel.

### Méthodes

#### `getTotalHoursByProject(Project $project): float`

Retourne le total d'heures passées sur un projet.

```php
public function getTotalHoursByProject(Project $project): float
{
    return $this->timeEntryRepository->sumDurationByProject($project->getId());
}
```

#### `getBillableHoursByProject(Project $project): float`

Retourne uniquement les heures facturables.

#### `getHoursByUserAndProject(User $user, Project $project): float`

Retourne les heures d'un collaborateur sur un projet donné.

#### `getWeeklyReport(User $user, \DateTimeInterface $weekStart): array`

Génère un rapport hebdomadaire pour un utilisateur.

```php
public function getWeeklyReport(User $user, \DateTimeInterface $weekStart): array
{
    $weekEnd = (clone $weekStart)->modify('+6 days');

    $entries = $this->timeEntryRepository->findByUserAndDateRange(
        $user,
        $weekStart,
        $weekEnd
    );

    $report = [
        'total_hours' => 0.0,
        'billable_hours' => 0.0,
        'by_project' => [],
        'by_day' => array_fill(0, 7, 0.0),
        'entries' => $entries,
    ];

    foreach ($entries as $entry) {
        $report['total_hours'] += $entry->getDuration();
        if ($entry->isBillable()) {
            $report['billable_hours'] += $entry->getDuration();
        }
        $dayIndex = (int)$entry->getDate()->format('N') - 1;
        $report['by_day'][$dayIndex] += $entry->getDuration();

        $projectId = $entry->getProject()->getId();
        $report['by_project'][$projectId] = ($report['by_project'][$projectId] ?? 0.0) + $entry->getDuration();
    }

    return $report;
}
```

#### `getMonthlyReport(User $user, int $year, int $month): array`

Rapport mensuel avec détail par projet et par semaine.

#### `calculateProjectProgress(Project $project): array`

Calcule l'avancement d'un projet en comparant heures budgétées vs heures réelles.

```php
public function calculateProjectProgress(Project $project): array
{
    $budget = $project->getBudget() ?? 0;
    $spent = $this->getTotalHoursByProject($project);
    $billableAmount = $this->getBillableAmount($project);

    return [
        'hours_spent' => $spent,
        'hours_budget' => $budget,
        'hours_remaining' => max(0, $budget - $spent),
        'progress_percent' => $budget > 0 ? min(100, round($spent / $budget * 100)) : 0,
        'billable_amount' => $billableAmount,
    ];
}
```

#### `getHourlyRateByProject(Project $project): float`

Calcule le taux horaire moyen pondéré du projet.

---

## 4. ProjectStatsService

**Fichier :** `src/Service/ProjectStatsService.php`

Génère toutes les statistiques et KPIs affichés dans le tableau de bord et les rapports.

### Méthodes

#### `getDashboardStats(): array`

Retourne les KPIs principaux pour le tableau de bord.

```php
public function getDashboardStats(): array
{
    $currentYear = (int)(new \DateTimeImmutable())->format('Y');
    $currentMonth = (int)(new \DateTimeImmutable())->format('m');

    return [
        // Projets
        'projects_active' => $this->projectRepository->countByStatus('in_progress'),
        'projects_total' => $this->projectRepository->count([]),
        'projects_new_this_month' => $this->projectRepository->countCreatedThisMonth(),

        // Facturation
        'revenue_ytd' => $this->invoiceRepository->sumPaidThisYear($currentYear),
        'revenue_this_month' => $this->invoiceRepository->sumPaidThisMonth($currentYear, $currentMonth),
        'invoices_pending' => $this->invoiceRepository->countByStatus('sent'),
        'invoices_overdue' => $this->invoiceRepository->countOverdue(),
        'outstanding_amount' => $this->invoiceRepository->sumOutstanding(),

        // Clients
        'clients_total' => $this->clientRepository->count(['isActive' => true]),
        'clients_new_this_month' => $this->clientRepository->countNewThisMonth(),

        // Temps
        'hours_this_month' => $this->timeEntryRepository->sumDurationThisMonth($currentYear, $currentMonth),
        'hours_billable_rate' => $this->timeEntryRepository->getBillableRate($currentYear, $currentMonth),
    ];
}
```

#### `getRevenueByMonth(int $year): array`

Retourne le CA mensuel pour l'année donnée (12 valeurs).

```php
public function getRevenueByMonth(int $year): array
{
    $data = array_fill(1, 12, 0.0);

    $results = $this->invoiceRepository->getMonthlyRevenue($year);
    foreach ($results as $row) {
        $data[(int)$row['month']] = (float)$row['total'];
    }

    return $data;
}
```

#### `getProjectsByStatus(): array`

Retourne le nombre de projets par statut pour les graphiques.

#### `getTopClientsByRevenue(int $limit = 5): array`

Retourne les N clients générateurs du plus grand CA.

#### `getProjectProfitability(Project $project): array`

Calcule la rentabilité d'un projet : honoraires contractuels vs temps facturé vs dépenses.

```php
public function getProjectProfitability(Project $project): array
{
    $fee = $project->getFee() ?? 0;
    $timeRevenue = $this->timeTrackingService->getBillableAmount($project);
    $expenses = $this->expenseRepository->sumByProject($project->getId());
    $invoiced = $this->invoiceRepository->sumByProject($project->getId());

    return [
        'fee_contracted' => $fee,
        'time_revenue' => $timeRevenue,
        'expenses' => $expenses,
        'invoiced' => $invoiced,
        'margin' => $invoiced - $expenses,
        'margin_rate' => $invoiced > 0 ? round(($invoiced - $expenses) / $invoiced * 100, 1) : 0,
    ];
}
```

#### `getMonthlyExpenses(int $year): array`

Retourne les dépenses mensuelles pour l'année donnée.

---

## 5. NotificationService

**Fichier :** `src/Service/NotificationService.php`

Centralise l'envoi de toutes les notifications email de l'application.

### Injection de dépendances

```php
class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private string $fromEmail,
        private string $fromName,
        private LoggerInterface $logger,
    ) {}
}
```

### Méthodes

#### `sendQuoteToClient(Quote $quote): void`

Envoie le devis au client par email avec le PDF en pièce jointe.

```php
public function sendQuoteToClient(Quote $quote): void
{
    $client = $quote->getClient();
    if (!$client->getEmail()) {
        $this->logger->warning('Client has no email', ['client_id' => $client->getId()]);
        return;
    }

    $pdfPath = $this->quoteService->generatePdf($quote);

    $email = (new TemplatedEmail())
        ->from(new Address($this->fromEmail, $this->fromName))
        ->to(new Address($client->getEmail(), $client->getFullName()))
        ->subject(sprintf('Devis %s — %s', $quote->getNumber(), $quote->getProject()?->getName() ?? ''))
        ->htmlTemplate('emails/quote.html.twig')
        ->context([
            'quote' => $quote,
            'client' => $client,
        ])
        ->attachFromPath($pdfPath, sprintf('%s.pdf', $quote->getNumber()));

    try {
        $this->mailer->send($email);
        $quote->setStatus(QuoteStatus::Sent);
    } catch (TransportExceptionInterface $e) {
        $this->logger->error('Failed to send quote email', [
            'quote_id' => $quote->getId(),
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

#### `sendInvoiceToClient(Invoice $invoice): void`

Envoie la facture au client avec le PDF en pièce jointe.

#### `sendPaymentReminder(Invoice $invoice): void`

Envoie un email de relance pour une facture en retard.

```php
public function sendPaymentReminder(Invoice $invoice): void
{
    $daysOverdue = (new \DateTimeImmutable())->diff($invoice->getDueDate())->days;

    $email = (new TemplatedEmail())
        ->from(new Address($this->fromEmail, $this->fromName))
        ->to(new Address($invoice->getClient()->getEmail(), $invoice->getClient()->getFullName()))
        ->subject(sprintf('Rappel — Facture %s en attente de règlement', $invoice->getNumber()))
        ->htmlTemplate('emails/payment_reminder.html.twig')
        ->context([
            'invoice' => $invoice,
            'days_overdue' => $daysOverdue,
        ]);

    $this->mailer->send($email);
}
```

#### `sendWelcomeEmail(User $user, string $plainPassword): void`

Envoie un email de bienvenue à un nouvel utilisateur avec ses identifiants.

#### `sendProjectDeadlineAlert(Project $project, User $assignee): void`

Envoie une alerte quand une échéance de projet approche.

#### `notifyAdminNewClient(Client $client): void`

Notifie les administrateurs de la création d'un nouveau client.

---

## Utilisation dans les contrôleurs

```php
#[Route('/invoices/{id}/send', name: 'invoice_send', methods: ['POST'])]
public function send(
    Invoice $invoice,
    InvoiceService $invoiceService,
    NotificationService $notificationService,
): Response
{
    // Générer le PDF
    $invoiceService->generatePdf($invoice);

    // Envoyer au client
    $notificationService->sendInvoiceToClient($invoice);

    $this->addFlash('success', 'Facture envoyée avec succès.');
    return $this->redirectToRoute('invoice_show', ['id' => $invoice->getId()]);
}
```

---

:::tip Tests unitaires
Chaque service dispose de sa suite de tests dans `tests/Service/`. Les services sont testables indépendamment grâce à l'injection de dépendances et aux interfaces :

```php
// tests/Service/InvoiceServiceTest.php
class InvoiceServiceTest extends TestCase
{
    private InvoiceService $service;

    protected function setUp(): void
    {
        $this->service = new InvoiceService(
            entityManager: $this->createMock(EntityManagerInterface::class),
            invoiceRepository: $this->createMock(InvoiceRepository::class),
            // ...
        );
    }
}
```
:::
