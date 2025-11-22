<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BulkEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Objet du mail',
                'attr' => [
                    'placeholder' => 'Annonce importante du club',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de renseigner un objet pour votre message.',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L’objet ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu du message',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => "Bonjour à tous,\n\nVoici les informations importantes concernant...",
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci d’écrire un message avant l’envoi.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}


