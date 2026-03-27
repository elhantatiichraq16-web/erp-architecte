<?php

namespace App\Form;

use App\Entity\Expense;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseType extends AbstractType
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
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => Expense::CATEGORIES,
                'placeholder' => '-- Sélectionner une catégorie --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (€)',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Description de la dépense'],
            ])
            ->add('fournisseur', TextType::class, [
                'label' => 'Fournisseur',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Fournisseur'],
            ])
            ->add('justificatif', TextType::class, [
                'label' => 'Justificatif',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Référence ou chemin du justificatif'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
        ]);
    }
}
