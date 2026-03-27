---
id: expenses
title: Dépenses
sidebar_label: Dépenses
description: Documentation du module de gestion des dépenses et notes de frais de l'ERP Architecte.
---

# Dépenses et Notes de Frais

Le module Dépenses permet d'enregistrer toutes les dépenses liées aux projets du cabinet : déplacements, achats de fournitures, repas professionnels, sous-traitance, etc.

---

## Objectif

- Centraliser toutes les dépenses liées aux projets
- Conserver les justificatifs numériques (photos de tickets)
- Calculer la rentabilité réelle des projets (honoraires − dépenses)
- Faciliter la préparation des remboursements de frais
- Préparer les exports comptables

---

## Categories de depenses

| Catégorie | Code | TVA récupérable |
|-----------|------|----------------|
| Déplacements | `travel` | Non (carburant : 80%) |
| Transports en commun | `transport` | Oui |
| Hébergement | `accommodation` | Oui |
| Repas professionnels | `meals` | Oui (à 50%) |
| Fournitures | `supplies` | Oui |
| Matériaux / maquettes | `materials` | Oui |
| Sous-traitance | `subcontracting` | Oui |
| Documentation | `documentation` | Oui |
| Frais postaux | `postage` | Oui |
| Divers | `misc` | Selon facture |

---

## Fonctionnalites

### Saisie de dépense

- Formulaire rapide de saisie
- Rattachement obligatoire à un projet
- Catégorisation de la dépense
- Saisie du montant HT et de la TVA
- Upload du justificatif (photo/PDF)
- Indication si la dépense est remboursable au collaborateur
- Description libre

### Gestion des justificatifs

- Upload de fichiers image (JPG, PNG) ou PDF
- Stockage sécurisé sur le serveur
- Prévisualisation dans l'interface
- Téléchargement du justificatif original

### Vue par projet

Dans la fiche projet, onglet "Dépenses" :
- Total des dépenses par catégorie
- Liste détaillée avec justificatifs
- Impact sur la rentabilité du projet

### Remboursements

- Marquer des dépenses comme remboursées
- Suivi des dépenses en attente de remboursement
- Export de la note de frais mensuelle par collaborateur

### Rapports

- Dépenses par catégorie (mois, trimestre, année)
- Dépenses par projet
- Comparaison dépenses vs budget
- Dépenses par collaborateur

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/expenses` | `expense_index` | Liste des dépenses |
| `GET` | `/expenses/new` | `expense_new` | Formulaire de saisie |
| `POST` | `/expenses/new` | `expense_new` | Enregistrer une dépense |
| `GET` | `/expenses/{id}` | `expense_show` | Détail d'une dépense |
| `GET` | `/expenses/{id}/edit` | `expense_edit` | Modifier |
| `POST` | `/expenses/{id}/refund` | `expense_mark_refunded` | Marquer comme remboursée |
| `GET` | `/expenses/report` | `expense_report` | Rapport des dépenses |
| `GET` | `/expenses/export` | `expense_export` | Export CSV |
| `DELETE` | `/expenses/{id}` | `expense_delete` | Supprimer |

---

## Formulaire de saisie

```php
class ExpenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [...])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('category', ChoiceType::class, [
                'choices' => ExpenseCategory::getChoices(),
            ])
            ->add('description', TextareaType::class)
            ->add('amount', MoneyType::class, ['label' => 'Montant HT'])
            ->add('vatAmount', MoneyType::class, [
                'label' => 'TVA',
                'required' => false,
            ])
            ->add('receipt', FileType::class, [
                'label' => 'Justificatif',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'],
                    ])
                ],
            ])
            ->add('isRefundable', CheckboxType::class, [
                'label' => 'Remboursable au collaborateur',
                'required' => false,
            ]);
    }
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Liste des dépenses]                   │
│  Tableau avec filtres catégorie + projet + période               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Formulaire de saisie dépense]         │
│  Formulaire avec upload de justificatif                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Taille max justificatif | `expenses.max_receipt_size` | `5M` |
| Catégories disponibles | `expenses.categories` | *(liste JSON)* |
| Seuil alerte dépenses | `expenses.alert_threshold` | `1000` |

---

:::info Intégration comptable
Les dépenses peuvent être exportées au format CSV compatible avec la plupart des logiciels comptables (Ciel, EBP, Sage). L'export inclut la date, le libellé, le montant HT, la TVA et le compte comptable suggéré par catégorie.
:::
