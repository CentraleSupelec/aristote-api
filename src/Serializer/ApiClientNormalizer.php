<?php

namespace App\Serializer;

use App\Entity\ApiClient;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ApiClientNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly ObjectNormalizer $objectNormalizer,
    ) {
    }

    public function normalize($apiClient, string $format = null, array $context = []): array
    {
        $data = $this->objectNormalizer->normalize($apiClient, $format, $context);
        if ($context['groups'] && in_array('enrichments_with_status', $context['groups'])) {
            $data['name'] = $apiClient->getName();
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof ApiClient;
    }
}
