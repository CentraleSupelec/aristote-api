<?php

namespace App\Model;

use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class AiEvaluationJobResponse
{
    #[OA\Property(property: 'enrichmentId', type: 'string')]
    #[Groups(groups: ['ai_evaluation_job'])]
    private ?Uuid $enrichmentId = null;

    #[OA\Property(property: 'enrichmentVersionId', type: 'string')]
    #[Groups(groups: ['ai_evaluation_job'])]
    private ?Uuid $enrichmentVersionId = null;

    #[OA\Property(property: 'transcript', type: 'object', ref: new Model(type: Transcript::class, groups: ['enrichment_versions']))]
    #[Groups(groups: ['ai_evaluation_job'])]
    private ?Transcript $transcript = null;

    #[OA\Property(property: 'multipleChoiceQuestions', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['ai_evaluation_job'])))]
    #[Groups(groups: ['ai_evaluation_job'])]
    private Collection $multipleChoiceQuestions;

    #[OA\Property(property: 'enrichmentVersionMetadata', type: 'object', ref: new Model(type: EnrichmentVersionMetadata::class, groups: ['ai_evaluation_job']))]
    #[Groups(groups: ['ai_evaluation_job'])]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    public function __construct()
    {
        $this->multipleChoiceQuestions = new ArrayCollection();
    }

    public function getEnrichmentId(): ?Uuid
    {
        return $this->enrichmentId;
    }

    public function setEnrichmentId(?Uuid $enrichmentId): self
    {
        $this->enrichmentId = $enrichmentId;

        return $this;
    }

    public function getEnrichmentVersionId(): ?Uuid
    {
        return $this->enrichmentVersionId;
    }

    public function setEnrichmentVersionId(?Uuid $enrichmentVersionId): self
    {
        $this->enrichmentVersionId = $enrichmentVersionId;

        return $this;
    }

    public function getTranscript(): ?Transcript
    {
        return $this->transcript;
    }

    public function setTranscript(?Transcript $transcript): self
    {
        $this->transcript = $transcript;

        return $this;
    }

    /**
     * @return Collection<int, MultipleChoiceQuestion>
     */
    public function getMultipleChoiceQuestions(): Collection
    {
        return $this->multipleChoiceQuestions;
    }

    public function setMultipleChoiceQuestions(Collection $multipleChoiceQuestion): self
    {
        $this->multipleChoiceQuestions = $multipleChoiceQuestion;

        return $this;
    }

    public function getEnrichmentVersionMetadata(): ?EnrichmentVersionMetadata
    {
        return $this->enrichmentVersionMetadata;
    }

    public function setEnrichmentVersionMetadata(?EnrichmentVersionMetadata $enrichmentVersionMetadata): static
    {
        $this->enrichmentVersionMetadata = $enrichmentVersionMetadata;

        return $this;
    }
}
