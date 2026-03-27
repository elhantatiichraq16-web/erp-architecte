<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'placeholder' => '-- Sélectionner un projet --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du document',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du document'],
            ])
            ->add('nomFichier', TextType::class, [
                'label' => 'Nom du fichier',
                'attr' => ['class' => 'form-control', 'placeholder' => 'nom_fichier.pdf'],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => Document::CATEGORIES,
                'placeholder' => '-- Sélectionner une catégorie --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('version', TextType::class, [
                'label' => 'Version',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'v1.0'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Notes'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
