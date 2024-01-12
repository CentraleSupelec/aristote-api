<?php

namespace App\Form;

use App\Model\AiEvaluationRequestPayload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiEvaluationRequestPayloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evaluations', CollectionType::class, [
                'entry_type' => MultipleChoiceQuestionType::class,
                'allow_add' => true,
            ])
            ->add('taskId', UuidType::class)
            ->add('failureCause')
            ->add('status')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AiEvaluationRequestPayload::class,
            'csrf_protection' => false,
        ]);
    }
}
