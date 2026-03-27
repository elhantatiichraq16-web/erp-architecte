<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Invoice;
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

class InvoiceType extends AbstractType
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
            ->add('quote', EntityType::class, [
                'label' => 'Devis associé',
                'class' => Quote::class,
                'choice_label' => fn($quote) => $quote->getNumero() . ' - ' . $quote->getObjet(),
                'required' => false,
                'placeholder' => '-- Aucun devis --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet',
                'attr' => ['class' => 'form-control', 'placeholder' => "Objet de la facture"],
            ])
            ->add('dateEmission', DateType::class, [
                'label' => "Date d'émission",
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateEcheance', DateType::class, [
                'label' => "Date d'échéance",
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Invoice::STATUSES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tauxTVA', NumberType::class, [
                'label' => 'Taux TVA (%)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => '20.00'],
            ])
            ->add('lines', CollectionType::class, [
                'label' => 'Lignes de facturation',
                'entry_type' => InvoiceLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => ['class' => 'invoice-lines-collection'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
