---
id: quotes
title: Devis
sidebar_label: Devis
description: Documentation du module de gestion des devis de l'ERP Architecte.
---

# Devis

Le module Devis permet de créer, envoyer et suivre les devis commerciaux adressés aux clients du cabinet. Un devis accepté peut être converti en facture en un clic.

---

## Objectif

Simplifier la création de propositions commerciales professionnelles :
- Devis multi-lignes avec calcul automatique
- Export PDF personnalisé aux couleurs du cabinet
- Envoi par email directement depuis l'interface
- Suivi du statut (brouillon, envoyé, accepté, refusé, expiré)
- Conversion rapide en facture

---

## Cycle de vie d'un devis

```
[Brouillon] ──────► [Envoyé] ──────► [Accepté] ──────► [Facture créée]
    │                   │                 │
    │                   └──────────► [Refusé]
    │                   └──────────► [Expiré]
    └── (suppression si jamais envoyé)
```

---

## Fonctionnalites

### Creation de devis

- Formulaire multi-lignes de prestations (ajout/suppression/réorganisation)
- Calcul automatique (quantité × prix unitaire, sous-total, TVA, total TTC)
- Sélection du client et du projet associé
- Date d'émission et date de validité
- Champ notes visibles et notes internes
- Taux de TVA configurable par ligne (ou global)

### Gestion des lignes

- Ajout de ligne en un clic (bouton "+")
- Suppression de ligne
- Réorganisation par glisser-déposer (drag & drop)
- Champ unité : heures, forfait, m², pièce, etc.
- Duplication d'une ligne existante

### Generation PDF

Le PDF est généré avec DomPDF et inclut :
- En-tête avec logo et informations du cabinet
- Coordonnées du client
- Tableau des prestations
- Totaux (HT, TVA, TTC)
- Mentions légales et conditions de paiement
- Pied de page avec numérotation

### Envoi par email

- Email envoyé avec le PDF en pièce jointe
- Template email personnalisable
- Message d'accompagnement libre
- Confirmation de lecture (optionnel)

### Conversion en facture

Quand un devis est accepté :
1. Changer le statut en "Accepté"
2. Cliquer sur "Créer la facture"
3. La facture est pré-remplie avec toutes les informations du devis
4. Lien bidirectionnel devis ↔ facture

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/quotes` | `quote_index` | Liste des devis |
| `GET` | `/quotes/new` | `quote_new` | Formulaire de création |
| `POST` | `/quotes/new` | `quote_new` | Créer un devis |
| `GET` | `/quotes/{id}` | `quote_show` | Détail d'un devis |
| `GET` | `/quotes/{id}/edit` | `quote_edit` | Modifier un devis |
| `POST` | `/quotes/{id}/edit` | `quote_edit` | Sauvegarder les modifications |
| `POST` | `/quotes/{id}/send` | `quote_send` | Envoyer au client par email |
| `POST` | `/quotes/{id}/accept` | `quote_accept` | Marquer comme accepté |
| `POST` | `/quotes/{id}/reject` | `quote_reject` | Marquer comme refusé |
| `GET` | `/quotes/{id}/pdf` | `quote_pdf` | Télécharger le PDF |
| `POST` | `/quotes/{id}/duplicate` | `quote_duplicate` | Dupliquer le devis |
| `POST` | `/quotes/{id}/invoice` | `quote_create_invoice` | Créer la facture |
| `DELETE` | `/quotes/{id}` | `quote_delete` | Supprimer un devis |

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Création de devis]                    │
│  Formulaire multi-lignes avec total calculé automatiquement      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Aperçu PDF du devis]                  │
│  PDF professionnel avec logo, prestations et totaux              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Numérotation automatique

Format : `DEV-YYYY-NNN`
- `DEV` : préfixe configurable
- `YYYY` : année en cours
- `NNN` : séquence sur 3 chiffres remise à zéro chaque année

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Préfixe des numéros | `quotes.prefix` | `DEV` |
| Durée de validité (jours) | `quotes.validity_days` | `30` |
| Taux TVA par défaut | `quotes.default_vat_rate` | `20` |
| Mentions légales | `quotes.legal_mentions` | *(configurable)* |
| Conditions de paiement | `quotes.payment_terms` | `30 jours fin de mois` |

---

:::info Modèles de devis
L'ERP permet de créer des modèles de devis prédéfinis pour les types de missions récurrents (rénovation standard, permis de construire, etc.). Consultez les [Paramètres](/modules/settings) pour configurer vos modèles.
:::
