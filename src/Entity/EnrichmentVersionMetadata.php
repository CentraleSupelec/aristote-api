<?php

namespace App\Entity;

use App\Repository\EnrichmentVersionMetadataRepository;
use App\Validator\Constraints as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrichmentVersionMetadataRepository::class)]
class EnrichmentVersionMetadata implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_job'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions', 'translation_post'])]
    private ?string $translatedTitle = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_job'])]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_post'])]
    private ?string $translatedDescription = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'topics', description: 'Topics', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_job'])]
    private ?array $topics = [];

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'translatedTopics', description: 'Translated topics', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_post'])]
    private ?array $translatedTopics = [];

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    #[AppAssert\DisciplineConstraint]
    private string $discipline;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    #[AppAssert\MediaTypeConstraint]
    private string $mediaType;

    #[ORM\OneToOne(inversedBy: 'enrichmentVersionMetadata', targetEntity: EnrichmentVersion::class)]
    private ?EnrichmentVersion $enrichmentVersion = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUpTitle = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUpDescription = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUpTopics = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUpDiscipline = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUpMediaType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?string $userFeedback = null;

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTranslatedTitle(): ?string
    {
        return $this->translatedTitle;
    }

    public function setTranslatedTitle(?string $translatedTitle): self
    {
        $this->translatedTitle = $translatedTitle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTranslatedDescription(): ?string
    {
        return $this->translatedDescription;
    }

    public function setTranslatedDescription(?string $translatedDescription): self
    {
        $this->translatedDescription = $translatedDescription;

        return $this;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function setTopics(array $topics): self
    {
        $this->topics = $topics;

        return $this;
    }

    public function getTranslatedTopics(): array
    {
        return $this->translatedTopics;
    }

    public function setTranslatedTopics(array $translatedTopics): self
    {
        $this->translatedTopics = $translatedTopics;

        return $this;
    }

    public function getDiscipline(): ?string
    {
        return $this->discipline;
    }

    public function setDiscipline(?string $discipline): self
    {
        $this->discipline = $discipline;

        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getEnrichmentVersion(): ?EnrichmentVersion
    {
        return $this->enrichmentVersion;
    }

    public function setEnrichmentVersion(?EnrichmentVersion $enrichmentVersion): self
    {
        $this->enrichmentVersion = $enrichmentVersion;

        return $this;
    }

    public function getThumbUpTitle(): ?bool
    {
        return $this->thumbUpTitle;
    }

    public function setThumbUpTitle(?bool $thumbUpTitle): self
    {
        $this->thumbUpTitle = $thumbUpTitle;

        return $this;
    }

    public function getThumbUpDescription(): ?bool
    {
        return $this->thumbUpDescription;
    }

    public function setThumbUpDescription(?bool $thumbUpDescription): self
    {
        $this->thumbUpDescription = $thumbUpDescription;

        return $this;
    }

    public function getThumbUpTopics(): ?bool
    {
        return $this->thumbUpTopics;
    }

    public function setThumbUpTopics(?bool $thumbUpTopics): self
    {
        $this->thumbUpTopics = $thumbUpTopics;

        return $this;
    }

    public function getThumbUpDiscipline(): ?bool
    {
        return $this->thumbUpDiscipline;
    }

    public function setThumbUpDiscipline(?bool $thumbUpDiscipline): self
    {
        $this->thumbUpDiscipline = $thumbUpDiscipline;

        return $this;
    }

    public function getThumbUpMediaType(): ?bool
    {
        return $this->thumbUpMediaType;
    }

    public function setThumbUpMediaType(?bool $thumbUpMediaType): self
    {
        $this->thumbUpMediaType = $thumbUpMediaType;

        return $this;
    }

    public function getUserFeedback(): ?string
    {
        return $this->userFeedback;
    }

    public function setUserFeedback(?string $userFeedback): self
    {
        $this->userFeedback = $userFeedback;

        return $this;
    }
}
