<?php

namespace App\Model;

use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema()]
class EnrichmentVersionUserEvaluationRequestPayload
{
    #[OA\Property(property: 'multipleChoiceQuestion', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['enrichment_version_evaluation'])))]
    private readonly Collection $multipleChoiceQuestions;

    #[OA\Property(property: 'enrichmentVersionMetadata', type: 'object', ref: new Model(type: EnrichmentVersionMetadata::class, groups: ['enrichment_version_evaluation']))]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

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
}
