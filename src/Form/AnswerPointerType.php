<?php

namespace App\Form;

use App\Entity\AnswerPointer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerPointerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', UuidType::class)
            ->add('startAnswerPointer')
            ->add('stopAnswerPointer')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnswerPointer::class,
            'csrf_protection' => false,
        ]);
    }
}
