---
id: entities
title: Entités
sidebar_label: Entités (BDD)
description: Documentation complète des 13 entités Doctrine de l'ERP Architecte avec leurs champs et relations.
---

# Entités Doctrine

L'ERP Architecte est composé de **13 entités** Doctrine mappées sur la base de données MySQL. Cette page décrit chaque entité avec ses champs, ses types et ses relations.

---

## Diagramme des relations

```
┌──────────┐       ┌──────────┐       ┌──────────┐
│   User   │──────<│EventItem │>──────│  Project │
└──────────┘       └──────────┘       └──────────┘
                                           │ 1
                                     ┌─────▼──────┐
     ┌──────────┐             ┌──────│   Client   │
     │  Quote   │>────────────┤      └────────────┘
     └──────────┘             │
          │ 1                 │
     ┌────▼─────┐             │      ┌────────────┐
     │QuoteItem │             └──────│   Invoice  │
     └──────────┘                    └────────────┘
                                           │ 1
                                     ┌─────▼──────┐
                                     │InvoiceItem │
                                     └────────────┘

┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│  TimeEntry   │   │   Expense    │   │   Document   │
│  (Project)   │   │  (Project)   │   │  (Project)   │
└──────────────┘   └──────────────┘   └──────────────┘

┌──────────────┐   ┌──────────────┐
│   Setting    │   │    Phase     │
│  (clé/val)   │   │  (Project)   │
└──────────────┘   └──────────────┘
```

---

## 1. User (Utilisateur)

Représente un utilisateur du système (architecte, assistant, administrateur).

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant auto-incrémenté |
| `email` | `string(180)` | Non | Email (unique, utilisé comme login) |
| `roles` | `json` | Non | Tableau des rôles : `ROLE_USER`, `ROLE_ADMIN` |
| `password` | `string` | Non | Mot de passe hashé (bcrypt/argon2) |
| `firstName` | `string(100)` | Non | Prénom |
| `lastName` | `string(100)` | Non | Nom de famille |
| `phone` | `string(20)` | Oui | Téléphone professionnel |
| `position` | `string(100)` | Oui | Poste (ex: "Architecte associé") |
| `avatar` | `string` | Oui | Chemin vers l'image de profil |
| `hourlyRate` | `decimal(10,2)` | Oui | Taux horaire (€/h) |
| `isActive` | `boolean` | Non | Compte actif/désactivé |
| `createdAt` | `datetime_immutable` | Non | Date de création |
| `updatedAt` | `datetime` | Oui | Dernière modification |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `OneToMany` | `TimeEntry` | Saisies de temps de cet utilisateur |
| `OneToMany` | `EventItem` | Événements créés par cet utilisateur |
| `OneToMany` | `Expense` | Notes de frais de cet utilisateur |

### Exemple de code

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    // ...
}
```

---

## 2. Client

Représente un client du cabinet (particulier ou société).

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `type` | `string(20)` | Non | `particulier` ou `entreprise` |
| `firstName` | `string(100)` | Oui | Prénom (particuliers) |
| `lastName` | `string(100)` | Oui | Nom (particuliers) |
| `companyName` | `string(200)` | Oui | Raison sociale (entreprises) |
| `siret` | `string(14)` | Oui | Numéro SIRET |
| `email` | `string(180)` | Oui | Email de contact |
| `phone` | `string(20)` | Oui | Téléphone |
| `address` | `string(255)` | Oui | Adresse postale |
| `city` | `string(100)` | Oui | Ville |
| `postalCode` | `string(10)` | Oui | Code postal |
| `country` | `string(2)` | Non | Code pays ISO (défaut: `FR`) |
| `notes` | `text` | Oui | Notes internes |
| `isActive` | `boolean` | Non | Actif/archivé |
| `createdAt` | `datetime_immutable` | Non | Date de création |
| `updatedAt` | `datetime` | Oui | Dernière modification |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `OneToMany` | `Project` | Projets de ce client |
| `OneToMany` | `Quote` | Devis établis pour ce client |
| `OneToMany` | `Invoice` | Factures de ce client |

---

## 3. Project (Projet)

Coeur du système : représente un projet d'architecture.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `reference` | `string(20)` | Non | Référence unique (ex: `PRJ-2024-001`) |
| `name` | `string(255)` | Non | Intitulé du projet |
| `description` | `text` | Oui | Description détaillée |
| `status` | `string(30)` | Non | Statut (voir enum ci-dessous) |
| `type` | `string(50)` | Oui | Type de mission (neuf, rénovation...) |
| `budget` | `decimal(12,2)` | Oui | Budget estimé (€ HT) |
| `fee` | `decimal(12,2)` | Oui | Honoraires contractuels (€ HT) |
| `startDate` | `date` | Oui | Date de démarrage |
| `endDate` | `date` | Oui | Date de fin prévue |
| `address` | `string(255)` | Oui | Adresse du chantier |
| `city` | `string(100)` | Oui | Ville du chantier |
| `postalCode` | `string(10)` | Oui | Code postal |
| `surface` | `decimal(10,2)` | Oui | Surface (m²) |
| `createdAt` | `datetime_immutable` | Non | Date de création |
| `updatedAt` | `datetime` | Oui | Dernière modification |

**Valeurs de statut :**

| Valeur | Label affiché |
|--------|--------------|
| `draft` | Brouillon |
| `in_progress` | En cours |
| `on_hold` | En pause |
| `completed` | Terminé |
| `archived` | Archivé |
| `cancelled` | Annulé |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Client` | Client propriétaire du projet |
| `OneToMany` | `Quote` | Devis liés au projet |
| `OneToMany` | `Invoice` | Factures liées au projet |
| `OneToMany` | `TimeEntry` | Saisies de temps du projet |
| `OneToMany` | `Expense` | Dépenses du projet |
| `OneToMany` | `EventItem` | Événements du projet |
| `OneToMany` | `Document` | Documents du projet |
| `OneToMany` | `Phase` | Phases du projet |

---

## 4. Quote (Devis)

Représente un devis commercial adressé à un client.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `number` | `string(20)` | Non | Numéro de devis (ex: `DEV-2024-042`) |
| `status` | `string(20)` | Non | `draft`, `sent`, `accepted`, `rejected`, `expired` |
| `issueDate` | `date` | Non | Date d'émission |
| `validUntil` | `date` | Oui | Date de validité |
| `subtotal` | `decimal(12,2)` | Non | Total HT |
| `vatRate` | `decimal(5,2)` | Non | Taux TVA (%) |
| `vatAmount` | `decimal(12,2)` | Non | Montant TVA |
| `total` | `decimal(12,2)` | Non | Total TTC |
| `notes` | `text` | Oui | Mentions particulières |
| `internalNotes` | `text` | Oui | Notes internes (non visibles sur le PDF) |
| `createdAt` | `datetime_immutable` | Non | Date de création |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Client` | Client destinataire |
| `ManyToOne` | `Project` | Projet concerné (optionnel) |
| `OneToMany` | `QuoteItem` | Lignes du devis |
| `OneToOne` | `Invoice` | Facture générée depuis ce devis |

---

## 5. QuoteItem (Ligne de devis)

Ligne de prestation dans un devis.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `description` | `text` | Non | Libellé de la prestation |
| `quantity` | `decimal(10,3)` | Non | Quantité |
| `unit` | `string(20)` | Oui | Unité (h, forfait, m², etc.) |
| `unitPrice` | `decimal(10,2)` | Non | Prix unitaire HT |
| `total` | `decimal(12,2)` | Non | Total ligne HT (`quantité × prix`) |
| `position` | `int` | Non | Ordre d'affichage |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Quote` | Devis parent |

---

## 6. Invoice (Facture)

Représente une facture émise.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `number` | `string(20)` | Non | Numéro de facture (ex: `FAC-2024-015`) |
| `status` | `string(20)` | Non | `draft`, `sent`, `paid`, `overdue`, `cancelled` |
| `type` | `string(20)` | Non | `invoice`, `deposit`, `credit_note` |
| `issueDate` | `date` | Non | Date d'émission |
| `dueDate` | `date` | Oui | Date d'échéance |
| `paidAt` | `datetime` | Oui | Date de paiement effectif |
| `subtotal` | `decimal(12,2)` | Non | Total HT |
| `vatRate` | `decimal(5,2)` | Non | Taux TVA (%) |
| `vatAmount` | `decimal(12,2)` | Non | Montant TVA |
| `total` | `decimal(12,2)` | Non | Total TTC |
| `depositPercent` | `decimal(5,2)` | Oui | Pourcentage d'acompte |
| `paymentMethod` | `string(30)` | Oui | Mode de paiement |
| `paymentTerms` | `string(100)` | Oui | Conditions de paiement |
| `notes` | `text` | Oui | Mentions légales / notes |
| `createdAt` | `datetime_immutable` | Non | Date de création |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Client` | Client facturé |
| `ManyToOne` | `Project` | Projet concerné |
| `OneToOne` | `Quote` | Devis d'origine (optionnel) |
| `OneToMany` | `InvoiceItem` | Lignes de la facture |

---

## 7. InvoiceItem (Ligne de facture)

Ligne de prestation dans une facture. Structure identique à `QuoteItem`.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `description` | `text` | Non | Libellé |
| `quantity` | `decimal(10,3)` | Non | Quantité |
| `unit` | `string(20)` | Oui | Unité |
| `unitPrice` | `decimal(10,2)` | Non | Prix unitaire HT |
| `total` | `decimal(12,2)` | Non | Total ligne HT |
| `position` | `int` | Non | Ordre d'affichage |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Invoice` | Facture parente |

---

## 8. TimeEntry (Saisie de temps)

Enregistre le temps passé par un utilisateur sur un projet.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `date` | `date` | Non | Date de la saisie |
| `duration` | `decimal(5,2)` | Non | Durée en heures (ex: `2.5` = 2h30) |
| `description` | `text` | Oui | Description de la tâche |
| `phase` | `string(50)` | Oui | Phase du projet (ESQ, APS, APD…) |
| `isBillable` | `boolean` | Non | Heures facturables (défaut: `true`) |
| `hourlyRate` | `decimal(10,2)` | Oui | Taux horaire appliqué |
| `createdAt` | `datetime_immutable` | Non | Date de création |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Project` | Projet concerné |
| `ManyToOne` | `User` | Collaborateur |

---

## 9. Expense (Dépense)

Note de frais ou dépense liée à un projet.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `date` | `date` | Non | Date de la dépense |
| `category` | `string(50)` | Non | Catégorie (déplacement, repas, matériel…) |
| `description` | `text` | Non | Description |
| `amount` | `decimal(10,2)` | Non | Montant HT |
| `vatAmount` | `decimal(10,2)` | Oui | TVA récupérable |
| `receipt` | `string` | Oui | Chemin vers le justificatif |
| `isRefundable` | `boolean` | Non | Remboursable au collaborateur |
| `isRefunded` | `boolean` | Non | Déjà remboursé |
| `createdAt` | `datetime_immutable` | Non | Date de création |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Project` | Projet concerné |
| `ManyToOne` | `User` | Collaborateur |

---

## 10. EventItem (Événement calendrier)

Événement affiché dans le module Calendrier.

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `title` | `string(255)` | Non | Titre de l'événement |
| `description` | `text` | Oui | Description |
| `type` | `string(30)` | Non | `meeting`, `deadline`, `site_visit`, `reminder` |
| `startAt` | `datetime` | Non | Début |
| `endAt` | `datetime` | Oui | Fin |
| `allDay` | `boolean` | Non | Événement toute la journée |
| `color` | `string(7)` | Oui | Couleur hexadécimale (`#3B82F6`) |
| `location` | `string(255)` | Oui | Lieu |
| `createdAt` | `datetime_immutable` | Non | Date de création |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Project` | Projet associé (optionnel) |
| `ManyToOne` | `User` | Créateur de l'événement |

---

## 11. Document

Fichier attaché à un projet (plan, rapport, contrat, etc.).

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `name` | `string(255)` | Non | Nom affiché |
| `fileName` | `string(255)` | Non | Nom du fichier sur le disque |
| `mimeType` | `string(100)` | Oui | Type MIME |
| `size` | `int` | Oui | Taille en octets |
| `category` | `string(50)` | Oui | Catégorie (plans, contrats, photos…) |
| `description` | `text` | Oui | Description |
| `version` | `string(10)` | Oui | Numéro de version (ex: `v1.2`) |
| `uploadedAt` | `datetime_immutable` | Non | Date d'upload |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Project` | Projet parent |
| `ManyToOne` | `User` | Utilisateur ayant uploadé le fichier |

---

## 12. Phase

Phase d'un projet d'architecture (ESQ, APS, APD, PC, etc.).

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `code` | `string(10)` | Non | Code de la phase (ESQ, APS, APD…) |
| `name` | `string(100)` | Non | Libellé complet |
| `description` | `text` | Oui | Description |
| `status` | `string(20)` | Non | `pending`, `in_progress`, `completed` |
| `feePercent` | `decimal(5,2)` | Oui | Part des honoraires (%) |
| `startDate` | `date` | Oui | Date de début |
| `endDate` | `date` | Oui | Date de fin prévue |
| `position` | `int` | Non | Ordre d'affichage |

### Relations

| Type | Entité liée | Description |
|------|------------|-------------|
| `ManyToOne` | `Project` | Projet parent |

**Phases standard architecture française :**

| Code | Libellé | Description |
|------|---------|-------------|
| ESQ | Esquisse | Première approche du projet |
| APS | Avant-Projet Sommaire | Orientation générale |
| APD | Avant-Projet Définitif | Définition complète |
| PC | Permis de Construire | Dossier administratif |
| PRO | Projet | Plans d'exécution |
| ACT | Assistance Contrats Travaux | Consultation entreprises |
| EXE | Études d'exécution | Plans de détail |
| OPC | Ordonnancement, Pilotage, Coordination | Coordination chantier |
| AOR | Assistance Opérations de Réception | Réception des travaux |

---

## 13. Setting (Paramètre)

Paramètres de configuration de l'application (clé/valeur).

### Champs

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | `int` | Non | Identifiant |
| `key` | `string(100)` | Non | Clé unique du paramètre |
| `value` | `text` | Oui | Valeur du paramètre |
| `type` | `string(20)` | Non | `string`, `integer`, `boolean`, `json` |
| `group` | `string(50)` | Oui | Groupe de paramètres |
| `label` | `string(255)` | Oui | Label affiché dans l'interface |
| `description` | `text` | Oui | Description du paramètre |
| `updatedAt` | `datetime` | Oui | Dernière modification |

**Paramètres prédéfinis :**

| Clé | Groupe | Description |
|-----|--------|-------------|
| `company.name` | Entreprise | Nom du cabinet |
| `company.siret` | Entreprise | SIRET |
| `company.address` | Entreprise | Adresse |
| `invoice.prefix` | Facturation | Préfixe des factures |
| `invoice.vat_default` | Facturation | TVA par défaut |
| `invoice.payment_terms` | Facturation | Délai de paiement |
| `quote.validity_days` | Devis | Durée de validité (jours) |
| `email.from` | Email | Expéditeur des emails |
