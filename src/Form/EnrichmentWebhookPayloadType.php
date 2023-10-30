<?php

namespace App\Form;

use App\Model\EnrichmentWebhookPayload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnrichmentWebhookPayloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', UuidType::class)
            ->add('status')
            ->add('failureCause')
            ->add('initialVersionId', UuidType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnrichmentWebhookPayload::class,
            'csrf_protection' => false,
        ]);
    }
}
