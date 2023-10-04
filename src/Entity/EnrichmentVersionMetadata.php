<?php

namespace App\Entity;

use App\Repository\EnrichmentVersionMetadataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'enrichmentVersionMetadata', targetEntity: Topic::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions'])]
    #[OA\Property(property: 'topics', description: 'Topics', type: 'array', items: new OA\Items(type: 'string'))]
    private Collection $topics;

    #[ORM\OneToMany(mappedBy: 'enrichmentVersionMetadata', targetEntity: Tag::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions'])]
    #[OA\Property(property: 'tags', description: 'Tags', type: 'array', items: new OA\Items(type: 'string'))]
    private Collection $tags;

    #[ORM\OneToOne(inversedBy: 'enrichmentVersionMetadata', targetEntity: EnrichmentVersion::class)]
    private ?EnrichmentVersion $enrichmentVersion = null;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Topic>
     */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function addTopic(Topic $topic): static
    {
        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
            $topic->setEnrichmentVersionMetadata($this);
        }

        return $this;
    }

    public function removeTopic(Topic $topic): static
    {
        // set the owning side to null (unless already changed)
        if ($this->topics->removeElement($topic) && $topic->getEnrichmentVersionMetadata() === $this) {
            $topic->setEnrichmentVersionMetadata(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->setEnrichmentVersionMetadata($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        // set the owning side to null (unless already changed)
        if ($this->topics->removeElement($tag) && $tag->getEnrichmentVersionMetadata() === $this) {
            $tag->setEnrichmentVersionMetadata(null);
        }

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
