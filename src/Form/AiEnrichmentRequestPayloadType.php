<?php

namespace App\Form;

use App\Model\AiEnrichmentRequestPayload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiEnrichmentRequestPayloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enrichmentVersionMetadata', EnrichmentVersionMetadataType::class)
            ->add('multipleChoiceQuestions', CollectionType::class, [
                'entry_type' => MultipleChoiceQuestionType::class,
                'allow_add' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AiEnrichmentRequestPayload::class,
            'csrf_protection' => false,
        ]);
    }
}
