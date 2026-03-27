<?php

namespace App\Form;

use App\Entity\Collaborator;
use App\Entity\Project;
use App\Entity\ProjectPhase;
use App\Entity\TimeEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('collaborator', EntityType::class, [
                'label' => 'Collaborateur',
                'class' => Collaborator::class,
                'choice_label' => function (Collaborator $collaborator): string {
                    return $collaborator->getNom() . ($collaborator->getPrenom() ? ' ' . $collaborator->getPrenom() : '');
                },
                'placeholder' => '-- Sélectionner un collaborateur --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'placeholder' => '-- Sélectionner un projet --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('phase', ChoiceType::class, [
                'label' => 'Phase',
                'choices' => array_flip(ProjectPhase::PHASES),
                'required' => false,
                'placeholder' => '-- Sélectionner une phase --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('heures', NumberType::class, [
                'label' => 'Heures',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00', 'step' => '0.25'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Description de la tâche'],
            ])
            ->add('facturable', CheckboxType::class, [
                'label' => 'Facturable',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeEntry::class,
        ]);
    }
}
