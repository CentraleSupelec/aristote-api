<?php

namespace App\Message;

use App\Entity\ApiClient;
use App\Model\EnrichmentCreationUrlRequestPayload;

class FileUploadFromUrlMessage
{
    public function __construct(
        private readonly string $enrichmentId,
        private readonly ApiClient $apiClient,
        private readonly EnrichmentCreationUrlRequestPayload $enrichmentCreationUrlRequestPayload
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

    public function getEnrichmentCreationUrlRequestPayload(): EnrichmentCreationUrlRequestPayload
    {
        return $this->enrichmentCreationUrlRequestPayload;
    }
}
