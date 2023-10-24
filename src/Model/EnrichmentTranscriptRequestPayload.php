<?php

namespace App\Model;

use App\Validator\Constraints as AppAssert;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[OA\Schema()]
class EnrichmentTranscriptRequestPayload
{
    #[OA\Property(property: 'transcript', type: 'file')]
    #[AppAssert\TranscriptFileConstraint]
    private ?UploadedFile $transcript = null;

    public function getTranscript(): ?UploadedFile
    {
        return $this->transcript;
    }

    public function setTranscript(?UploadedFile $transcript): self
    {
        $this->transcript = $transcript;

        return $this;
    }
}
