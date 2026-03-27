<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Project;
use App\Repository\DocumentRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;

#[Route('/documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentRepository $documentRepository,
        private ProjectRepository $projectRepository,
        private SluggerInterface $slugger,
        private string $uploadsDirectory,
    ) {}

    #[Route('', name: 'app_document_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $projectId = $request->query->get('project', '');
        $categorie = $request->query->get('categorie', '');

        $qb = $this->em->createQueryBuilder()
            ->select('d')
            ->from(Document::class, 'd')
            ->join('d.project', 'p')
            ->orderBy('d.createdAt', 'DESC');

        if ($projectId) {
            $qb->andWhere('p.id = :projectId')->setParameter('projectId', (int) $projectId);
        }

        if ($categorie) {
            $qb->andWhere('d.categorie = :categorie')->setParameter('categorie', $categorie);
        }

        $documents = $qb->getQuery()->getResult();

        return $this->render('document/index.html.twig', [
            'documents'  => $documents,
            'projects'   => $this->projectRepository->findBy([], ['nom' => 'ASC']),
            'categories' => Document::CATEGORIES,
            'projectId'  => (int) $projectId,
            'categorie'  => $categorie,
        ]);
    }

    #[Route('/new', name: 'app_document_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $document = new Document();

        $form = $this->createFormBuilder($document)
            ->add('project', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label'        => 'Projet',
                'class'        => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'placeholder'  => '-- Sélectionner un projet --',
                'attr'         => ['class' => 'form-select'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du document',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('categorie', ChoiceType::class, [
                'label'       => 'Catégorie',
                'choices'     => Document::CATEGORIES,
                'placeholder' => '-- Sélectionner --',
                'attr'        => ['class' => 'form-select'],
            ])
            ->add('version', TextType::class, [
                'label'    => 'Version',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'ex: v1.0'],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('fichier', FileType::class, [
                'label'       => 'Fichier',
                'mapped'      => false,
                'required'    => true,
                'constraints' => [
                    new File([
                        'maxSize'          => '20M',
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr'  => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('fichier')->getData();

            if ($fichier) {
                $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $this->slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $fichier->guessExtension();

                try {
                    $fichier->move($this->uploadsDirectory, $newFilename);
                    $document->setNomFichier($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier : ' . $e->getMessage());

                    return $this->render('document/new.html.twig', [
                        'document' => $document,
                        'form'     => $form,
                    ]);
                }
            }

            $this->em->persist($document);
            $this->em->flush();

            $this->addFlash('success', 'Document ajouté avec succès.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_document_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Document $document): Response
    {
        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_document_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Document $document): Response
    {
        $form = $this->createFormBuilder($document)
            ->add('project', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label'        => 'Projet',
                'class'        => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'placeholder'  => '-- Sélectionner un projet --',
                'attr'         => ['class' => 'form-select'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du document',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('categorie', ChoiceType::class, [
                'label'       => 'Catégorie',
                'choices'     => Document::CATEGORIES,
                'placeholder' => '-- Sélectionner --',
                'attr'        => ['class' => 'form-select'],
            ])
            ->add('version', TextType::class, [
                'label'    => 'Version',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'ex: v1.0'],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('fichier', FileType::class, [
                'label'       => 'Remplacer le fichier (optionnel)',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '20M',
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr'  => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichier = $form->get('fichier')->getData();

            if ($fichier) {
                $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $this->slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $fichier->guessExtension();

                try {
                    $fichier->move($this->uploadsDirectory, $newFilename);

                    // Remove the old file if it exists
                    $oldFile = $this->uploadsDirectory . '/' . $document->getNomFichier();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }

                    $document->setNomFichier($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier : ' . $e->getMessage());
                }
            }

            $this->em->flush();

            $this->addFlash('success', 'Document modifié avec succès.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_document_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Document $document): Response
    {
        if ($this->isCsrfTokenValid('delete_document_' . $document->getId(), $request->request->get('_token'))) {
            // Remove the physical file
            $filePath = $this->uploadsDirectory . '/' . $document->getNomFichier();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->em->remove($document);
            $this->em->flush();
            $this->addFlash('success', 'Document supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_document_index');
    }

    /**
     * Download a document file.
     */
    #[Route('/{id}/download', name: 'app_document_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(Document $document): Response
    {
        $filePath = $this->uploadsDirectory . '/' . $document->getNomFichier();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Fichier introuvable sur le serveur.');

            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->file($filePath, $document->getNom() . '.' . pathinfo($document->getNomFichier(), PATHINFO_EXTENSION));
    }
}
