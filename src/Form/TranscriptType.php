<?php

namespace App\Form;

use App\Entity\Transcript;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranscriptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text')
            ->add('translatedText')
            ->add('sentences', TextType::class)
            ->add('translatedSentences', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transcript::class,
            'csrf_protection' => false,
        ]);
    }
}
