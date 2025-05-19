<?php

namespace App\Model;

use App\Entity\Transcript;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[OA\Schema()]
class AiEnrichmentJobResponse
{
    #[OA\Property(property: 'enrichmentId', type: 'string')]
    #[Groups(groups: ['enrichment_job'])]
    private ?Uuid $enrichmentId = null;

    #[OA\Property(property: 'enrichmentVersionId', type: 'string')]
    #[Groups(groups: ['enrichment_job'])]
    private ?Uuid $enrichmentVersionId = null;

    #[OA\Property(property: 'transcript', type: 'object', ref: new Model(type: Transcript::class, groups: ['enrichment_versions']))]
    #[Groups(groups: ['enrichment_job'])]
    private ?Transcript $transcript = null;

    #[OA\Property(property: 'mediaTypes', description: 'List of media types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_job'])]
    private array $mediaTypes = [];

    #[OA\Property(property: 'disciplines', description: 'List of disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_job'])]
    private array $disciplines = [];

    #[OA\Property(property: 'generateMetadata', description: 'Request metadata generation', type: 'boolean')]
    #[Groups(groups: ['enrichment_job'])]
    private bool $generateMetadata = false;

    #[OA\Property(property: 'generateQuiz', description: 'Request quiz generation', type: 'boolean')]
    #[Groups(groups: ['enrichment_job'])]
    private bool $generateQuiz = false;

    #[OA\Property(property: 'generateNotes', description: 'Request notes generation', type: 'boolean')]
    #[Groups(groups: ['enrichment_job'])]
    private bool $generateNotes = false;

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

    public function getMediaTypes(): array
    {
        return $this->mediaTypes;
    }

    public function setMediaTypes(array $mediaTypes): self
    {
        $this->mediaTypes = $mediaTypes;

        return $this;
    }

    public function getDisciplines(): array
    {
        return $this->disciplines;
    }

    public function setDisciplines(array $disciplines): self
    {
        $this->disciplines = $disciplines;

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

    public function getGenerateMetadata(): bool
    {
        return $this->generateMetadata;
    }

    public function setGenerateMetadata(bool $generateMetadata): self
    {
        $this->generateMetadata = $generateMetadata;

        return $this;
    }

    public function getGenerateQuiz(): bool
    {
        return $this->generateQuiz;
    }

    public function setGenerateQuiz(bool $generateQuiz): self
    {
        $this->generateQuiz = $generateQuiz;

        return $this;
    }

    public function getGenerateNotes(): bool
    {
        return $this->generateNotes;
    }

    public function setGenerateNotes(bool $generateNotes): self
    {
        $this->generateNotes = $generateNotes;

        return $this;
    }
}
