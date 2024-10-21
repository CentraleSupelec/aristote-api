<?php

namespace App\Form;

use App\Model\TranslationRequestPayload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslationRequestPayloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enrichmentVersionMetadata', EnrichmentVersionMetadataType::class)
            ->add('multipleChoiceQuestions', CollectionType::class, [
                'entry_type' => MultipleChoiceQuestionType::class,
                'allow_add' => true,
            ])
            ->add('transcript', TranscriptType::class, [
                'required' => false,
            ]
            )
            ->add('transcriptFile', FileType::class)
            ->add('notes')
            ->add('taskId', UuidType::class)
            ->add('failureCause')
            ->add('status')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TranslationRequestPayload::class,
            'csrf_protection' => false,
        ]);
    }
}
