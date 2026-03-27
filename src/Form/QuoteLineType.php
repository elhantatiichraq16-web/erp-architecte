<?php

namespace App\Form;

use App\Entity\QuoteLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Désignation'],
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('unite', ChoiceType::class, [
                'label' => 'Unité',
                'choices' => [
                    'Forfait'  => 'forfait',
                    'Heure'    => 'heure',
                    'm²'       => 'm²',
                    'm³'       => 'm³',
                    'ml'       => 'ml',
                    'u'        => 'u',
                    'Ensemble' => 'ensemble',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('prixUnitaireHT', MoneyType::class, [
                'label' => 'Prix unitaire HT (€)',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('ordre', HiddenType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteLine::class,
        ]);
    }
}
