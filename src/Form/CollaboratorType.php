<?php

namespace App\Form;

use App\Entity\Collaborator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollaboratorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'required' => false,
                'choices' => [
                    'Architecte DPLG'         => 'Architecte DPLG',
                    "Architecte d'intérieur"  => "Architecte d'intérieur",
                    'Dessinateur projeteur'   => 'Dessinateur projeteur',
                    'Ingénieur structure'     => 'Ingénieur structure',
                    'Assistant projet'        => 'Assistant projet',
                    'Stagiaire'               => 'Stagiaire',
                ],
                'placeholder' => '-- Sélectionner un rôle --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tauxHoraire', MoneyType::class, [
                'label' => 'Taux horaire (€/h)',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Téléphone'],
            ])
            ->add('couleur', ColorType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-color'],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collaborator::class,
        ]);
    }
}
