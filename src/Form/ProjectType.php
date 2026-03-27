<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Collaborator;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du projet',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du projet'],
            ])
            ->add('client', EntityType::class, [
                'label' => 'Client',
                'class' => Client::class,
                'choice_label' => function (Client $client): string {
                    return $client->getNom() . ($client->getPrenom() ? ' ' . $client->getPrenom() : '')
                        . ($client->getSociete() ? ' (' . $client->getSociete() . ')' : '');
                },
                'placeholder' => '-- Sélectionner un client --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('adresseChantier', TextType::class, [
                'label' => 'Adresse du chantier',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse du chantier'],
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('montantHonoraires', MoneyType::class, [
                'label' => 'Montant des honoraires (€)',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('budgetPrevisionnel', MoneyType::class, [
                'label' => 'Budget prévisionnel (€)',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFinPrevisionnelle', DateType::class, [
                'label' => 'Date de fin prévisionnelle',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Project::STATUSES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('couleur', ColorType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-color'],
            ])
            ->add('collaborators', EntityType::class, [
                'label' => 'Collaborateurs',
                'class' => Collaborator::class,
                'choice_label' => function (Collaborator $collaborator): string {
                    return $collaborator->getNom() . ($collaborator->getPrenom() ? ' ' . $collaborator->getPrenom() : '');
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label_attr' => ['class' => 'form-check-label'],
                'attr' => ['class' => 'form-check-input'],
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
            'data_class' => Project::class,
        ]);
    }
}
