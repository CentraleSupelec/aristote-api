<?php

namespace App\Serializer;

use App\Entity\MultipleChoiceQuestion;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MultipleChoiceQuestionNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $objectNormalizer,
    ) {
    }

    public function normalize($enrichment, string $format = null, array $context = []): array
    {
        $data = $this->objectNormalizer->normalize($enrichment, $format, $context);

        if (isset($data['evaluation'])) {
            $data['evaluation'] = json_decode((string) $data['evaluation'], null, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof MultipleChoiceQuestion;
    }
}