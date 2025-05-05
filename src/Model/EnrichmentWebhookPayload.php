<?php

namespace App\Model;

use App\Entity\Enrichment;
use OpenApi\Attributes as OA;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class EnrichmentWebhookPayload
{
    #[OA\Property(property: 'id', description: 'Enrichment ID', type: 'string')]
    private ?Uuid $id = null;

    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Choice(callback: [Enrichment::class, 'getPossibleStatuses'], multiple: false)]
    private ?string $status = null;

    #[OA\Property(property: 'initialVersionId', description: "Enrichment's intiail version ID", type: 'string')]
    private ?Uuid $initialVersionId = null;

    #[OA\Property(property: 'failureCause', description: 'Failure cause', type: 'string')]
    private ?string $failureCause = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Choice(callback: [Enrichment::class, 'getPossibleCompletedSteps'], multiple: false)]
    private ?string $completedStep = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): static
    {
        $this->id = $id;

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

    public function getInitialVersionId(): ?Uuid
    {
        return $this->initialVersionId;
    }

    public function setInitialVersionId(?Uuid $initialVersionId): static
    {
        $this->initialVersionId = $initialVersionId;

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

    public function getCompletedStep(): ?string
    {
        return $this->completedStep;
    }

    public function setCompletedStep(?string $completedStep): self
    {
        $this->completedStep = $completedStep;

        return $this;
    }
}
