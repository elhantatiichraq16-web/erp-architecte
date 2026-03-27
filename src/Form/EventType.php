<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => "Titre de l'événement"],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description'],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('allDay', CheckboxType::class, [
                'label' => 'Journée entière',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'required' => false,
                'placeholder' => '-- Aucun projet --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => Event::TYPES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('couleur', ColorType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-color'],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Lieu'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
