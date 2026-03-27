---
id: settings
title: Paramètres
sidebar_label: Paramètres
description: Documentation du module de configuration et paramètres de l'ERP Architecte.
---

# Paramètres

Le module Paramètres permet de configurer l'ERP Architecte selon les besoins spécifiques de votre cabinet : informations de l'entreprise, personnalisation des documents, options de facturation, gestion des utilisateurs.

---

## Objectif

- Personnaliser l'ERP aux couleurs et aux pratiques de votre cabinet
- Configurer les informations légales affichées sur les documents
- Gérer les utilisateurs et leurs permissions
- Ajuster les comportements par défaut des modules

---

## Sections de configuration

### Entreprise

Informations du cabinet affichées sur tous les documents (factures, devis, emails) :

| Paramètre | Clé | Description |
|-----------|-----|-------------|
| Nom du cabinet | `company.name` | Raison sociale |
| Adresse | `company.address` | Adresse complète |
| Code postal | `company.postal_code` | Code postal |
| Ville | `company.city` | Ville |
| Téléphone | `company.phone` | Numéro de téléphone |
| Email | `company.email` | Email de contact |
| Site web | `company.website` | URL du site |
| SIRET | `company.siret` | Numéro SIRET (14 chiffres) |
| TVA intracommunautaire | `company.vat_number` | Numéro TVA |
| Logo | `company.logo` | Fichier image (PNG/SVG) |

### Facturation

| Paramètre | Clé | Valeur par défaut |
|-----------|-----|-------------------|
| Préfixe factures | `invoices.prefix` | `FAC` |
| Prochain numéro | `invoices.next_number` | Auto-incrémenté |
| TVA par défaut (%) | `invoices.default_vat` | `20` |
| Délai de paiement (jours) | `invoices.payment_days` | `30` |
| Mode de paiement par défaut | `invoices.payment_method` | `Virement bancaire` |
| Conditions de paiement | `invoices.payment_terms` | *(texte légal)* |
| Mentions légales | `invoices.legal_text` | *(mentions obligatoires)* |
| IBAN | `invoices.iban` | *(à renseigner)* |
| BIC | `invoices.bic` | *(à renseigner)* |

### Devis

| Paramètre | Clé | Valeur par défaut |
|-----------|-----|-------------------|
| Préfixe devis | `quotes.prefix` | `DEV` |
| Durée de validité (jours) | `quotes.validity_days` | `30` |
| TVA par défaut (%) | `quotes.default_vat` | `20` |
| Message d'accompagnement | `quotes.email_message` | *(modèle email)* |

### Projets

| Paramètre | Clé | Valeur par défaut |
|-----------|-----|-------------------|
| Préfixe références | `projects.reference_prefix` | `PRJ` |
| Types de mission | `projects.mission_types` | *(liste JSON)* |
| Phases disponibles | `projects.phases` | *(liste JSON)* |

### Notifications et emails

| Paramètre | Clé | Description |
|-----------|-----|-------------|
| Expéditeur emails | `email.from` | Adresse d'expédition |
| Nom expéditeur | `email.from_name` | Nom affiché dans les emails |
| Notification facture en retard | `notifications.overdue_invoice` | `true` |
| Notification devis expirant | `notifications.expiring_quote` | `true` |
| Alerte budget projet | `notifications.project_budget_alert` | `true` |

---

## Gestion des utilisateurs

Accessible uniquement aux administrateurs (`ROLE_ADMIN`).

### Liste des utilisateurs

Tableau de tous les utilisateurs avec :
- Nom, email, rôle
- Statut (actif/inactif)
- Dernière connexion
- Actions : modifier, désactiver, réinitialiser le mot de passe

### Création d'un utilisateur

```php
// Routes
GET  /settings/users/new     -> user_new     (formulaire)
POST /settings/users/new     -> user_new     (création)
```

Champs du formulaire :
- Prénom, Nom
- Email (identifiant unique)
- Rôle : `ROLE_USER` ou `ROLE_ADMIN`
- Taux horaire
- Poste
- Mot de passe temporaire (envoyé par email)

### Rôles et permissions

| Rôle | Description | Accès |
|------|-------------|-------|
| `ROLE_USER` | Utilisateur standard | Ses projets, ses saisies de temps, son profil |
| `ROLE_ADMIN` | Administrateur | Tout l'ERP + gestion utilisateurs + paramètres |

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/settings` | `settings_index` | Page principale |
| `POST` | `/settings/company` | `settings_company` | Sauvegarder infos entreprise |
| `POST` | `/settings/invoices` | `settings_invoices` | Sauvegarder config facturation |
| `POST` | `/settings/quotes` | `settings_quotes` | Sauvegarder config devis |
| `POST` | `/settings/notifications` | `settings_notifications` | Sauvegarder notifications |
| `GET` | `/settings/users` | `settings_users` | Liste des utilisateurs |
| `GET` | `/settings/users/new` | `user_new` | Créer un utilisateur |
| `POST` | `/settings/users/new` | `user_new` | Sauvegarder |
| `GET` | `/settings/users/{id}/edit` | `user_edit` | Modifier un utilisateur |
| `POST` | `/settings/users/{id}/toggle` | `user_toggle` | Activer/désactiver |
| `POST` | `/settings/logo` | `settings_logo` | Uploader le logo |

---

## Controleur des paramètres

```php
// src/Controller/SettingsController.php
#[Route('/settings', name: 'settings_')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    public function __construct(
        private SettingRepository $settingRepository,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $settings = $this->settingRepository->getAllGrouped();

        return $this->render('settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/company', name: 'company', methods: ['POST'])]
    public function company(Request $request): Response
    {
        $data = $request->request->all();

        foreach ($data as $key => $value) {
            $setting = $this->settingRepository->findOneBy(['key' => "company.{$key}"]);
            if ($setting) {
                $setting->setValue($value);
            }
        }

        $this->em->flush();
        $this->addFlash('success', 'Informations sauvegardées.');

        return $this->redirectToRoute('settings_index');
    }
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Paramètres généraux]                  │
│  Onglets : Entreprise / Facturation / Devis / Utilisateurs       │
└─────────────────────────────────────────────────────────────────┘
```

---

:::tip Import/Export de la configuration
Un export de toutes les clés de configuration est possible depuis la page Paramètres (format JSON). Cela permet de transférer la configuration d'un environnement à un autre (dev → production).
:::

:::info Paramètres sensibles
Les paramètres sensibles (IBAN, clés API) sont stockés en base de données chiffrés. En production, préférez l'utilisation de variables d'environnement pour les données vraiment confidentielles.
:::
