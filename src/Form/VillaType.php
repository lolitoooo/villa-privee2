<?php

namespace App\Form;

use App\Entity\Villa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class VillaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un titre',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une description',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix par nuit',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un prix',
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être positif',
                    ]),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Localisation',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer une localisation',
                    ]),
                ],
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Nombre de chambres',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nombre de chambres',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de chambres doit être positif',
                    ]),
                ],
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Nombre de salles de bain',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nombre de salles de bain',
                    ]),
                    new Positive([
                        'message' => 'Le nombre de salles de bain doit être positif',
                    ]),
                ],
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité (nombre de personnes)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la capacité',
                    ]),
                    new Positive([
                        'message' => 'La capacité doit être positive',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Count([
                        'max' => 6,
                        'maxMessage' => 'Vous ne pouvez pas télécharger plus de {{ limit }} images',
                    ]),
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '2M',
                                'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximum autorisée est {{ limit }} {{ suffix }}.',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG)',
                            ])
                        ]
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Villa::class,
        ]);
    }
}
