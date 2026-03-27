---
id: documents
title: Documents
sidebar_label: Documents
description: Documentation du module de gestion documentaire de l'ERP Architecte.
---

# Gestion des Documents

Le module Documents centralise tous les fichiers liés aux projets du cabinet : plans DWG, maquettes 3D, photos de chantier, contrats, rapports, permis de construire, etc.

---

## Objectif

- Centraliser les documents de chaque projet dans un espace unique
- Organiser les fichiers par catégorie et par version
- Faciliter le partage et l'accès aux documents
- Conserver un historique des versions

---

## Categories de documents

| Catégorie | Description | Formats typiques |
|-----------|-------------|-----------------|
| Plans | Plans architecturaux | DWG, PDF, RVT |
| Rendus | Visualisations 3D | JPG, PNG, PDF |
| Photos | Photos de chantier | JPG, PNG |
| Contrats | Contrats de mission | PDF, DOCX |
| Rapports | Comptes-rendus | PDF, DOCX |
| Administratif | Permis, autorisations | PDF |
| Études | Études techniques | PDF |
| Divers | Autres fichiers | Tout format |

---

## Fonctionnalites

### Liste des documents

- Affichage en grille (icônes) ou liste (tableau)
- Filtres par catégorie, projet, type de fichier
- Recherche par nom de fichier
- Tri par date, nom, taille

### Upload de fichiers

- Upload par glisser-déposer (drag & drop)
- Upload multiple (plusieurs fichiers simultanément)
- Barre de progression de l'upload
- Validation du type et de la taille des fichiers
- Formats autorisés : PDF, DWG, RVT, SKP, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP

### Gestion des versions

- Possibilité d'uploader une nouvelle version d'un document existant
- Historique des versions avec dates
- Téléchargement d'une version antérieure

### Actions disponibles

- Téléchargement du fichier
- Prévisualisation en ligne (PDF, images)
- Renommer le document
- Changer la catégorie
- Ajouter/modifier la description et la version
- Supprimer (avec confirmation)

### Partage

- Génération d'un lien de téléchargement sécurisé (durée limitée)
- Partage par email directement depuis l'interface

---

## Routes

| Méthode | URL | Nom de la route | Description |
|---------|-----|----------------|-------------|
| `GET` | `/documents` | `document_index` | Liste globale des documents |
| `GET` | `/documents/project/{id}` | `document_project` | Documents d'un projet |
| `POST` | `/documents/upload` | `document_upload` | Upload d'un fichier |
| `GET` | `/documents/{id}` | `document_show` | Détail d'un document |
| `GET` | `/documents/{id}/download` | `document_download` | Télécharger |
| `POST` | `/documents/{id}/edit` | `document_edit` | Modifier les métadonnées |
| `POST` | `/documents/{id}/new-version` | `document_new_version` | Uploader une nouvelle version |
| `GET` | `/documents/{id}/share` | `document_share` | Générer un lien partagé |
| `DELETE` | `/documents/{id}` | `document_delete` | Supprimer |

---

## Configuration de l'upload

```yaml
# config/packages/vich_uploader.yaml
vich_uploader:
    db_driver: orm
    mappings:
        document_file:
            uri_prefix: /uploads/documents
            upload_destination: '%kernel.project_dir%/public/uploads/documents'
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
            inject_on_load: true
            delete_on_update: true
            delete_on_remove: true
```

---

## Contr oleur d'upload

```php
#[Route('/documents/upload', name: 'document_upload', methods: ['POST'])]
public function upload(
    Request $request,
    EntityManagerInterface $em,
    ProjectRepository $projectRepository,
): JsonResponse
{
    $file = $request->files->get('file');
    $projectId = $request->request->get('project_id');

    if (!$file || !$file->isValid()) {
        return $this->json(['error' => 'Fichier invalide'], 400);
    }

    $project = $projectRepository->find($projectId);
    $document = new Document();
    $document->setName($file->getClientOriginalName());
    $document->setProject($project);
    $document->setUploadedAt(new \DateTimeImmutable());
    $document->setMimeType($file->getMimeType());
    $document->setSize($file->getSize());
    $document->setFile($file);  // VichUploader gère le déplacement

    $em->persist($document);
    $em->flush();

    return $this->json([
        'id' => $document->getId(),
        'name' => $document->getName(),
        'url' => $this->generateUrl('document_download', ['id' => $document->getId()]),
    ]);
}
```

---

## Capture d'ecran

```
┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Documents d'un projet]                │
│  Vue grille avec icônes par type de fichier                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  [Screenshot placeholder — Zone drag & drop upload]              │
│  Interface d'upload avec barre de progression                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Options de configuration

| Paramètre | Clé Setting | Valeur par défaut |
|-----------|-------------|-------------------|
| Taille maximale par fichier | `documents.max_file_size` | `50M` |
| Extensions autorisées | `documents.allowed_extensions` | `pdf,dwg,rvt,...` |
| Durée lien partage (jours) | `documents.share_link_days` | `7` |
| Quota par projet (Mo) | `documents.project_quota` | `500` |

---

:::warning Sauvegardes
Les fichiers uploadés sont stockés dans `public/uploads/documents/`. Assurez-vous d'inclure ce répertoire dans votre stratégie de sauvegarde. En production, configurez un stockage objet (S3, OVH Object Storage) plutôt qu'un disque local.
:::
