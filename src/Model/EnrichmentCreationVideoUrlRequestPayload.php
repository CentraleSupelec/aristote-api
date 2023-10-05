<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationVideoUrlRequestPayload extends EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'videoUrl', description: 'A video URL (accessible without authentification)', type: 'string')]
    #[Assert\Url(message: 'The given videoUrl is not a valid URL')]
    #[Assert\NotBlank]
    private ?string $videoUrl = null;

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }
}
