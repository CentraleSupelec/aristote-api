<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationVideoUploadRequestPayload extends EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'videoFile', description: 'A video file', type: 'file')]
    #[Assert\NotBlank]
    private ?UploadedFile $videoFile = null;

    public function getVideoFile(): ?UploadedFile
    {
        return $this->videoFile;
    }

    public function setVideoFile(?UploadedFile $videoFile): self
    {
        $this->videoFile = $videoFile;

        return $this;
    }
}
