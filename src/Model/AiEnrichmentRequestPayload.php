<?php

namespace App\Model;

use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class AiEnrichmentRequestPayload
{
    #[OA\Property(property: 'enrichmentVersionMetadata', type: 'object', ref: new Model(type: EnrichmentVersionMetadata::class, groups: ['enrichment_versions']))]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    #[OA\Property(property: 'multipleChoiceQuestions', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['ai_enrichment_post'])))]
    private readonly Collection $multipleChoiceQuestions;

    #[OA\Property(property: 'notes', type: 'string')]
    private ?string $notes = null;

    #[OA\Property(property: 'translatedNotes', type: 'string')]
    private ?string $translatedNotes = null;

    #[OA\Property(property: 'taskId', type: 'string')]
    private ?Uuid $taskId = null;

    #[OA\Property(property: 'failureCause', type: 'string')]
    private ?string $failureCause = null;

    #[OA\Property(property: 'status', type: 'string')]
    private ?string $status = null;

    public function __construct()
    {
        $this->multipleChoiceQuestions = new ArrayCollection();
    }

    public function getEnrichmentVersionMetadata(): ?EnrichmentVersionMetadata
    {
        return $this->enrichmentVersionMetadata;
    }

    public function setEnrichmentVersionMetadata(?EnrichmentVersionMetadata $enrichmentVersionMetadata): self
    {
        $this->enrichmentVersionMetadata = $enrichmentVersionMetadata;

        return $this;
    }

    /**
     * @return Collection<int, MultipleChoiceQuestion>
     */
    public function getMultipleChoiceQuestions(): Collection
    {
        return $this->multipleChoiceQuestions;
    }

    public function addMultipleChoiceQuestion(MultipleChoiceQuestion $multipleChoiceQuestion): static
    {
        if (!$this->multipleChoiceQuestions->contains($multipleChoiceQuestion)) {
            $this->multipleChoiceQuestions->add($multipleChoiceQuestion);
        }

        return $this;
    }

    public function removeMultipleChoiceQuestion(MultipleChoiceQuestion $multipleChoiceQuestion): static
    {
        $this->multipleChoiceQuestions->removeElement($multipleChoiceQuestion);

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTranslatedNotes(): ?string
    {
        return $this->translatedNotes;
    }

    public function setTranslatedNotes(?string $translatedNotes): self
    {
        $this->translatedNotes = $translatedNotes;

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
