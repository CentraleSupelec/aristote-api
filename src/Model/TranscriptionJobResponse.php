<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class TranscriptionJobResponse
{
    #[OA\Property(property: 'enrichmentId', type: 'string')]
    private ?Uuid $enrichmentId = null;

    #[OA\Property(property: 'mediaTemporaryUrl', type: 'string')]
    private ?string $mediaTemporaryUrl = null;

    #[OA\Property(property: 'language', type: 'string')]
    private ?string $language = null;

    public function getEnrichmentId(): ?Uuid
    {
        return $this->enrichmentId;
    }

    public function setEnrichmentId(?Uuid $enrichmentId): self
    {
        $this->enrichmentId = $enrichmentId;

        return $this;
    }

    public function getMediaTemporaryUrl(): ?string
    {
        return $this->mediaTemporaryUrl;
    }

    public function setMediaTemporaryUrl(?string $mediaTemporaryUrl): self
    {
        $this->mediaTemporaryUrl = $mediaTemporaryUrl;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }
}
