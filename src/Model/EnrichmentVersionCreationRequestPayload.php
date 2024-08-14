<?php

namespace App\Model;

use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[OA\Schema()]
class EnrichmentVersionCreationRequestPayload
{
    #[OA\Property(property: 'transcript', type: 'file')]
    #[AppAssert\TranscriptFileConstraint]
    private ?UploadedFile $transcript = null;

    #[OA\Property(property: 'enrichmentVersionMetadata', type: 'object', ref: new Model(type: EnrichmentVersionMetadata::class, groups: ['enrichment_versions']))]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    #[OA\Property(property: 'multipleChoiceQuestions', type: 'array', items: new OA\Items(ref: new Model(type: MultipleChoiceQuestion::class, groups: ['enrichment_version_creation'])))]
    private readonly Collection $multipleChoiceQuestions;

    #[OA\Property(property: 'notes', type: 'string')]
    private ?bool $notes = null;

    #[OA\Property(property: 'translatedNotes', type: 'string')]
    private ?bool $translatedNotes = null;

    #[OA\Property(property: 'translate', type: 'boolean')]
    private ?bool $translate = null;

    public function __construct()
    {
        $this->multipleChoiceQuestions = new ArrayCollection();
    }

    public function getTranscript(): ?UploadedFile
    {
        return $this->transcript;
    }

    public function setTranscript(?UploadedFile $transcript): self
    {
        $this->transcript = $transcript;

        return $this;
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

    public function getTranslate(): ?bool
    {
        return $this->translate;
    }

    public function setTranslate(?bool $translate): self
    {
        $this->translate = $translate;

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
}
