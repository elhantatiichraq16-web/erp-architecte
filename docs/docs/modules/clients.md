---
id: clients
title: Gestion des Clients
sidebar_label: Clients
description: Documentation du module de gestion des clients de l'ERP Architecte.
---

# Gestion des Clients

Le module Clients permet de gérer l'annuaire complet des clients du cabinet : particuliers et entreprises, avec l'historique de leurs projets, devis et factures.

---

## Objectif

Centraliser toutes les informations sur les clients du cabinet et accéder rapidement à leur historique complet :
- Coordonnées complètes
- Liste des projets
- Historique des devis et factures
- Notes internes

---

## Fonctionnalites

### Liste des clients

- Tableau paginé avec recherche globale (DataTables)
- Filtres : type (particulier/entreprise), statut (actif/archivé)
- Tri par nom, date de création, nombre de projets
- Export CSV / Excel
- Indicateur visuel du nombre de projets actifs par client

### Fiche client

Chaque client dispose d'une fiche détaillée avec :

**Onglet Informations**
- Coordonnées complètes (adresse, téléphone, email)
- SIRET (pour les entreprises)
- Notes internes

**Onglet Projets**
- Liste des projets avec statut et budget
- Lien rapide vers chaque projet

**Onglet Devis**
- Historique de tous les devis avec statut
- Montants HT et TTC
- Actions : voir, télécharger PDF

**Onglet Factures**
- Historique des factures
- Solde en attente de paiement
- Actions : voir, envoyer, marquer comme payée

**Onglet Statistiques**
- CA total généré par ce client
- Nombre de projets par statut
- Évolution du CA sur les 3 dernières années

### Création / Modification

Formulaire de création avec :
- Choix du type (particulier ou entreprise)
- Champs conditionnels selon le type
- Validation SIRET pour les entreprises
- Vérification unicité de l'email

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/clients` | `client_index` | Liste des clients |
| `GET` | `/clients/new` | `client_new` | Formulaire de création |
| `POST` | `/clients/new` | `client_new` | Sauvegarder un nouveau client |
| `GET` | `/clients/{id}` | `client_show` | Fiche client |
| `GET` | `/clients/{id}/edit` | `client_edit` | Formulaire de modification |
| `POST` | `/clients/{id}/edit` | `client_edit` | Sauvegarder les modifications |
| `POST` | `/clients/{id}/delete` | `client_delete` | Archiver/supprimer un client |
| `GET` | `/clients/export` | `client_export` | Export CSV |

---

## Formulaire

```php
// src/Form/ClientType.php
class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Particulier' => 'particulier',
                    'Entreprise' => 'entreprise',
                ],
                'attr' => ['class' => 'client-type-selector'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'field-particulier'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['class' => 'field-particulier'],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'attr' => ['class' => 'field-entreprise'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'constraints' => [new Siret()],
            ])
            ->add('email', EmailType::class)
            ->add('phone', TelType::class, ['required' => false])
            ->add('address', TextareaType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('postalCode', TextType::class, ['required' => false])
            ->add('notes', TextareaType::class, ['required' => false]);
    }
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Liste des clients]                    │
│  Tableau avec filtres, recherche et boutons d'action             │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Fiche client]                         │
│  Onglets : Infos / Projets / Devis / Factures / Stats            │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Éléments par page | `clients.per_page` | `15` |
| Validation SIRET | `clients.validate_siret` | `true` |
| Pays par défaut | `clients.default_country` | `FR` |

---

:::warning Suppression des clients
Un client ne peut pas être supprimé s'il possède des projets, devis ou factures. L'action "Supprimer" archive le client (isActive = false) et le masque des listes principales tout en conservant l'historique.
:::
