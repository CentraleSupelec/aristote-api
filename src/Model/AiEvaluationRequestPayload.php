<?php

namespace App\Model;

use App\Entity\MultipleChoiceQuestion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class AiEvaluationRequestPayload
{
    #[OA\Property(property: 'evaluations', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['ai_evaluation_post'])))]
    private readonly Collection $evaluations;

    #[OA\Property(property: 'taskId', type: 'string')]
    private ?Uuid $taskId = null;

    #[OA\Property(property: 'failureCause', type: 'string')]
    private ?string $failureCause = null;

    #[OA\Property(property: 'status', type: 'string')]
    private ?string $status = null;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
    }

    /**
     * @return Collection<int, MultipleChoiceQuestion>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
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
