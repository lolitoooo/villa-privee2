<?php

namespace App\Form;

use App\Entity\ReservationOption;
use App\Entity\Option;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('option', EntityType::class, [
                'class' => Option::class,
                'choice_label' => 'name',
                'label' => 'Option',
                'required' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'min' => 1,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationOption::class,
        ]);
    }
}
