<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationFileUploadRequestPayload extends EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'file', description: 'A file', type: 'file')]
    #[Assert\NotBlank]
    private ?UploadedFile $file = null;

    #[OA\Property(property: 'originalFileName', type: 'string')]
    private ?string $originalFileName = null;

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(?string $originalFileName): self
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }
}
