<?php

namespace App\Serializer;

use App\Entity\EnrichmentVersionMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EnrichmentVersionMetadataNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $objectNormalizer,
    ) {
    }

    public function normalize($enrichment, string $format = null, array $context = []): array
    {
        $data = $this->objectNormalizer->normalize($enrichment, $format, $context);

        $tags = [];
        foreach ($data['tags'] as $tag) {
            $tags[] = $tag['text'];
        }
        $data['tags'] = $tags;

        $topics = [];
        foreach ($data['topics'] as $topic) {
            $topics[] = $topic['text'];
        }
        $data['topics'] = $topics;

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof EnrichmentVersionMetadata;
    }
}
