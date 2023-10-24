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

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $title = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'topics', description: 'Topics', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_versions'])]
    private ?array $topics = [];

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
}
