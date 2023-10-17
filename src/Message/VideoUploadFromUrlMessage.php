<?php

namespace App\Message;

use App\Entity\ApiClient;
use App\Model\EnrichmentCreationVideoUrlRequestPayload;

class VideoUploadFromUrlMessage
{
    public function __construct(
        private readonly string $enrichmentId,
        private readonly ApiClient $apiClient,
        private readonly EnrichmentCreationVideoUrlRequestPayload $enrichmentCreationVideoUrlRequestPayload
    ) {
    }

    public function getEnrichmentId(): string
    {
        return $this->enrichmentId;
    }

    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    public function getEnrichmentCreationVideoUrlRequestPayload(): EnrichmentCreationVideoUrlRequestPayload
    {
        return $this->enrichmentCreationVideoUrlRequestPayload;
    }
}
