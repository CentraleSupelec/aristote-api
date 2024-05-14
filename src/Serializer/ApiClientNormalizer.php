<?php

namespace App\Serializer;

use App\Entity\ApiClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiClientNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function normalize($apiClient, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($apiClient, $format, $context);
        if ($context['groups'] && in_array('enrichments_with_status', $context['groups'])) {
            $data['name'] = $apiClient->getName();
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof ApiClient;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ApiClient::class => true,
        ];
    }
}
