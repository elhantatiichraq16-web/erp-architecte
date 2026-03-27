<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank(['message' => 'Le nom est obligatoire.'])],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom'],
            ])
            ->add('societe', TextType::class, [
                'label' => 'Société',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Société'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Code postal'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ville'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Téléphone'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank(['message' => "L'email est obligatoire."])],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Email'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'SIRET'],
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
            'data_class' => Client::class,
        ]);
    }
}
