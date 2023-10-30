<?php

namespace App\Model;

use App\Validator\Constraints as AppAssert;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class EnrichmentTranscriptRequestPayload
{
    #[OA\Property(property: 'transcript', type: 'file')]
    #[AppAssert\TranscriptFileConstraint]
    private ?UploadedFile $transcript = null;

    #[OA\Property(property: 'taskId', type: 'string')]
    private ?Uuid $taskId = null;

    #[OA\Property(property: 'failureCause', type: 'string')]
    private ?string $failureCause = null;

    #[OA\Property(property: 'status', type: 'string')]
    private ?string $status = null;

    public function getTranscript(): ?UploadedFile
    {
        return $this->transcript;
    }

    public function setTranscript(?UploadedFile $transcript): self
    {
        $this->transcript = $transcript;

        return $this;
    }

    public function getTaskId(): ?Uuid
    {
        return $this->taskId;
    }

    public function setTaskId(?Uuid $taskId): self
    {
        $this->taskId = $taskId;

        return $this;
    }

    public function getFailureCause(): ?string
    {
        return $this->failureCause;
    }

    public function setFailureCause(?string $failureCause): self
    {
        $this->failureCause = $failureCause;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
