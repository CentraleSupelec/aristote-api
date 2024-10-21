<?php

namespace App\Serializer;

use App\Entity\MultipleChoiceQuestion;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MultipleChoiceQuestionNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function normalize($multipleChoiceQuestion, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($multipleChoiceQuestion, $format, $context);

        if (isset($data['evaluation'])) {
            $data['evaluation'] = json_decode((string) $data['evaluation'], null, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MultipleChoiceQuestion;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MultipleChoiceQuestion::class => true,
        ];
    }
}
