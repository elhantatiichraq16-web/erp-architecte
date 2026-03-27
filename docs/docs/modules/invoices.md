---
id: invoices
title: Facturation
sidebar_label: Facturation
description: Documentation du module de facturation de l'ERP Architecte.
---

# Facturation

Le module Facturation gère l'ensemble du cycle de facturation du cabinet : création des factures, suivi des paiements, relances automatiques et exports comptables.

---

## Objectif

Automatiser et sécuriser la facturation du cabinet :
- Numérotation séquentielle et irréversible conforme à la législation française
- Suivi en temps réel des encaissements
- Relances automatiques des impayés
- Export compatible avec les logiciels comptables

---

## Types de factures

| Type | Code | Description |
|------|------|-------------|
| **Facture d'acompte** | `deposit` | Acompte en début de mission (ex: 30%) |
| **Facture de situation** | `invoice` | Facturation d'une phase ou d'une période |
| **Facture finale** | `invoice` | Solde de mission |
| **Avoir** | `credit_note` | Annulation ou rectification d'une facture |

---

## Statuts des factures

| Statut | Couleur | Description |
|--------|---------|-------------|
| `draft` | Gris | Brouillon, non envoyé |
| `sent` | Bleu | Envoyé au client, en attente |
| `paid` | Vert | Payé et encaissé |
| `overdue` | Rouge | Délai de paiement dépassé |
| `cancelled` | Gris clair | Annulée (avoir émis) |

---

## Fonctionnalites

### Création de facture

- Formulaire identique au module Devis
- Champs supplémentaires : date d'échéance, mode de paiement, références bancaires
- Pré-remplissage depuis un devis accepté
- Calcul automatique des totaux HT/TVA/TTC

### Tableau de bord facturation

Vue d'ensemble de la trésorerie :
- Montant total facturé (mois en cours / année)
- Montant encaissé vs en attente
- Factures en retard avec nombre de jours
- Graphique de l'encours client

### Suivi des paiements

- Marquer une facture comme payée (date et mode de paiement)
- Paiements partiels (saisie d'acomptes)
- Historique des paiements par facture

### Relances automatiques

Système de relances configurable :
1. **J+0** : Envoi de la facture
2. **J+15** : Rappel amiable si non payée
3. **J+30** : Première relance formelle
4. **J+45** : Deuxième relance avec mise en demeure

### Export PDF

PDF de facture conforme aux exigences légales françaises :
- Numéro de facture unique et séquentiel
- Date d'émission et d'échéance
- Coordonnées complètes du vendeur et de l'acheteur
- SIRET du cabinet
- Détail des prestations avec TVA
- Mentions obligatoires (escompte, pénalités de retard)
- RIB / coordonnées bancaires

### Export comptable

Export des factures au format :
- CSV (compatible Excel)
- Format FEC (Fichier des Écritures Comptables)

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/invoices` | `invoice_index` | Liste des factures |
| `GET` | `/invoices/new` | `invoice_new` | Créer une facture |
| `POST` | `/invoices/new` | `invoice_new` | Sauvegarder |
| `GET` | `/invoices/{id}` | `invoice_show` | Détail de la facture |
| `GET` | `/invoices/{id}/edit` | `invoice_edit` | Modifier |
| `POST` | `/invoices/{id}/send` | `invoice_send` | Envoyer au client |
| `POST` | `/invoices/{id}/pay` | `invoice_mark_paid` | Marquer comme payée |
| `POST` | `/invoices/{id}/remind` | `invoice_remind` | Envoyer une relance |
| `GET` | `/invoices/{id}/pdf` | `invoice_pdf` | Télécharger le PDF |
| `POST` | `/invoices/{id}/credit-note` | `invoice_credit_note` | Émettre un avoir |
| `GET` | `/invoices/export` | `invoice_export` | Export CSV/FEC |

---

## Mentions légales obligatoires (France)

La facture inclut automatiquement :

```
Facture n° {number} — Date : {issue_date}
Date d'échéance : {due_date}

{company_name} — SIRET {siret}
{company_address}

En cas de retard de paiement, des pénalités de retard au taux annuel de 3 fois
le taux d'intérêt légal seront appliquées, ainsi qu'une indemnité forfaitaire
pour frais de recouvrement de 40 €.

Pas d'escompte pour règlement anticipé.
TVA non applicable, article 293 B du CGI. (si applicable)
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Liste des factures]                   │
│  Filtres statut + période, montants, indicateurs de retard       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Aperçu PDF facture]                   │
│  Facture professionnelle avec mentions légales                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Préfixe numérotation | `invoices.prefix` | `FAC` |
| TVA par défaut | `invoices.default_vat` | `20` |
| Délai de paiement (jours) | `invoices.payment_days` | `30` |
| Mode de paiement par défaut | `invoices.default_payment_method` | `Virement bancaire` |
| IBAN affiché | `invoices.iban` | *(à configurer)* |
| BIC/SWIFT | `invoices.bic` | *(à configurer)* |
| Mentions légales | `invoices.legal_text` | *(configurable)* |

---

:::danger Numérotation des factures
Conformément à la législation française (article 289 du CGI), la numérotation des factures doit être **séquentielle, sans rupture et sans doublon**. L'ERP gère cette contrainte automatiquement. Ne modifiez jamais manuellement les numéros de factures.
:::
