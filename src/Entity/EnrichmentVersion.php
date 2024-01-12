<?php

namespace App\Entity;

use App\Repository\EnrichmentVersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrichmentVersionRepository::class)]
class EnrichmentVersion
{
    use TimestampableEntity;

    public static function getSortFields(): array
    {
        return [
            'text',
            'createdAt',
            'updatedAt',
            'text',
        ];
    }

    // Redifined to add group
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(groups: ['enrichment_versions'])]
    protected $createdAt;

    // Redifined to add group
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(groups: ['enrichment_versions'])]
    protected $updatedAt;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(groups: ['enrichments', 'enrichment_versions'])]
    #[OA\Property(property: 'id', description: 'Enrichment version ID.', type: 'string')]
    private ?Uuid $id = null;

    #[ORM\OneToOne(mappedBy: 'enrichmentVersion', targetEntity: EnrichmentVersionMetadata::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions'])]
    #[Assert\Valid]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    #[ORM\OneToOne(mappedBy: 'enrichmentVersion', targetEntity: Transcript::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions_with_transcript'])]
    private ?Transcript $transcript = null;

    #[ORM\OneToMany(mappedBy: 'enrichmentVersion', targetEntity: MultipleChoiceQuestion::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $multipleChoiceQuestions;

    #[ORM\ManyToOne(inversedBy: 'versions', targetEntity: Enrichment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enrichment $enrichment = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(groups: ['enrichment_versions'])]
    private bool $initialVersion = false;

    public function __construct()
    {
        $this->multipleChoiceQuestions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEnrichmentVersionMetadata(): ?EnrichmentVersionMetadata
    {
        return $this->enrichmentVersionMetadata;
    }

    public function setEnrichmentVersionMetadata(?EnrichmentVersionMetadata $enrichmentVersionMetadata): static
    {
        $this->enrichmentVersionMetadata = $enrichmentVersionMetadata;
        $enrichmentVersionMetadata->setEnrichmentVersion($this);

        return $this;
    }

    public function getTranscript(): ?Transcript
    {
        return $this->transcript;
    }

    public function setTranscript(?Transcript $transcript): static
    {
        $this->transcript = $transcript;
        $transcript->setEnrichmentVersion($this);

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
            $multipleChoiceQuestion->setEnrichmentVersion($this);
        }

        return $this;
    }

    public function removeMultipleChoiceQuestion(MultipleChoiceQuestion $multipleChoiceQuestion): static
    {
        // set the owning side to null (unless already changed)
        if ($this->multipleChoiceQuestions->removeElement($multipleChoiceQuestion) && $multipleChoiceQuestion->getEnrichmentVersion() === $this) {
            $multipleChoiceQuestion->setEnrichmentVersion(null);
        }

        return $this;
    }

    public function getEnrichment(): ?Enrichment
    {
        return $this->enrichment;
    }

    public function setEnrichment(?Enrichment $enrichment): static
    {
        $this->enrichment = $enrichment;

        return $this;
    }

    public function isInitialVersion(): bool
    {
        return $this->initialVersion;
    }

    public function setInitialVersion(bool $initialVersion): self
    {
        $this->initialVersion = $initialVersion;

        return $this;
    }
}
