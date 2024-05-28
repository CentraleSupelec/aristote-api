<?php

namespace App\Form;

use App\Entity\MultipleChoiceQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultipleChoiceQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', UuidType::class)
            ->add('question')
            ->add('translatedQuestion')
            ->add('explanation')
            ->add('translatedExplanation')
            ->add('choices', CollectionType::class, [
                'entry_type' => ChoiceType::class,
                'allow_add' => true,
            ])
            ->add('evaluation')
            ->add('answerPointer', AnswerPointerType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MultipleChoiceQuestion::class,
            'csrf_protection' => false,
        ]);
    }
}
