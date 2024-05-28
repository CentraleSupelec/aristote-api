<?php

namespace App\Serializer;

use App\Entity\Transcript;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TranscriptNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function normalize($transcript, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($transcript, $format, $context);
        if (isset($data['sentences'])) {
            $data['sentences'] = json_decode((string) $data['sentences'], null, 512, JSON_THROW_ON_ERROR);
        }

        if (isset($data['translatedSentences'])) {
            $data['translatedSentences'] = json_decode((string) $data['translatedSentences'], null, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Transcript;
    }

    public function getSupportedTypes(string|null $format): array
    {
        return [
            Transcript::class => true,
        ];
    }
}
