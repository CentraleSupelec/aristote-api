<?php

namespace App\Model;

use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class TranslationRequestPayload
{
    #[OA\Property(property: 'enrichmentVersionMetadata', type: 'object', ref: new Model(type: EnrichmentVersionMetadata::class, groups: ['translation_post']))]
    #[Groups(groups: ['default'])]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    #[OA\Property(property: 'multipleChoiceQuestions', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['translation_post'])))]
    #[Groups(groups: ['default'])]
    private readonly Collection $multipleChoiceQuestions;

    #[OA\Property(property: 'transcriptFile', type: 'file')]
    #[Groups(groups: ['multipart'])]
    #[AppAssert\TranscriptFileConstraint]
    private ?UploadedFile $transcriptFile = null;

    #[OA\Property(property: 'transcript', type: 'object', ref: new Model(type: Transcript::class, groups: ['translation_post']))]
    #[Groups(groups: ['json'])]
    private ?Transcript $transcript = null;

    #[OA\Property(property: 'taskId', type: 'string')]
    #[Groups(groups: ['default'])]
    private ?Uuid $taskId = null;

    #[OA\Property(property: 'failureCause', type: 'string')]
    #[Groups(groups: ['default'])]
    private ?string $failureCause = null;

    #[OA\Property(property: 'status', type: 'string')]
    #[Groups(groups: ['default'])]
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

    public function getTranscriptFile(): ?UploadedFile
    {
        return $this->transcriptFile;
    }

    public function setTranscriptFile(?UploadedFile $transcriptFile): self
    {
        $this->transcriptFile = $transcriptFile;

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

    public function getTranscript(): ?Transcript
    {
        return $this->transcript;
    }

    public function setTranscript(?Transcript $transcript): static
    {
        $this->transcript = $transcript;

        return $this;
    }
}
