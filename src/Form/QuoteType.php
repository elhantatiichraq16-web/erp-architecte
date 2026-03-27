<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\Quote;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => fn($project) => $project->getReference() . ' - ' . $project->getNom(),
                'required' => false,
                'placeholder' => '-- Aucun projet --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Objet du devis'],
            ])
            ->add('dateCreation', DateType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateValidite', DateType::class, [
                'label' => 'Date de validité',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Quote::STATUSES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tauxTVA', NumberType::class, [
                'label' => 'Taux TVA (%)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => '20.00'],
            ])
            ->add('lines', CollectionType::class, [
                'label' => 'Lignes du devis',
                'entry_type' => QuoteLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => ['class' => 'quote-lines-collection'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
