<?php

namespace App\Serializer;

use App\Entity\Transcript;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TranscriptNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $objectNormalizer,
    ) {
    }

    public function normalize($transcript, string $format = null, array $context = []): array
    {
        $data = $this->objectNormalizer->normalize($transcript, $format, $context);
        if (isset($data['sentences'])) {
            $data['sentences'] = json_decode((string) $data['sentences'], null, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Transcript;
    }
}
