<?php

namespace App\Entity;

use App\Repository\EnrichmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrichmentRepository::class)]
class Enrichment
{
    use TimestampableEntity;
    final public const STATUS_PENDING = 'PENDING';
    final public const STATUS_SUCCESS = 'SUCCESS';
    final public const STATUS_FAILURE = 'FAILURE';

    public static function getPossibleStatuses(): array
    {
        return [
            self::STATUS_PENDING => self::STATUS_PENDING,
            self::STATUS_SUCCESS => self::STATUS_SUCCESS,
            self::STATUS_FAILURE => self::STATUS_FAILURE,
        ];
    }

    public static function getSortFields(): array
    {
        return [
            'status',
            'createdAt',
            'updatedAt',
        ];
    }

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(groups: ['enrichments'])]
    #[OA\Property(property: 'id', description: 'Enrichment ID', type: 'string')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'ownedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'identifier')]
    private ?ApiClient $createdBy = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Choice(callback: [self::class, 'getPossibleStatuses'], multiple: false)]
    #[Groups(groups: ['enrichments'])]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: 'enrichment', targetEntity: EnrichmentVersion::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $versions;

    #[Groups(groups: ['enrichments'])]
    #[OA\Property(property: 'initialVersionId', description: "Enrichment's intiail version ID", type: 'string')]
    public function getInitialVersionId(): ?Uuid
    {
        $initialVersion = $this->versions->findFirst(fn (int $index, EnrichmentVersion $enrichmentVersion) => $enrichmentVersion->isInitialVersion());

        if ($initialVersion) {
            /* @var EnrichmentVersion $initialVersion */
            return $initialVersion->getId();
        }

        return null;
    }

    #[Groups(groups: ['enrichments'])]
    private ?string $failureCause = null;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCreatedBy(): ?ApiClient
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?ApiClient $apiClient): static
    {
        $this->createdBy = $apiClient;

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

    /**
     * @return Collection<int, EnrichmentVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(EnrichmentVersion $enrichmentVersion): static
    {
        if (!$this->versions->contains($enrichmentVersion)) {
            $this->versions->add($enrichmentVersion);
            $enrichmentVersion->setEnrichment($this);
        }

        return $this;
    }

    public function removeVersion(EnrichmentVersion $enrichmentVersion): static
    {
        // set the owning side to null (unless already changed)
        if ($this->versions->removeElement($enrichmentVersion) && $enrichmentVersion->getEnrichment() === $this) {
            $enrichmentVersion->setEnrichment(null);
        }

        return $this;
    }

    public function getFailureCause(): ?string
    {
        return $this->failureCause;
    }
}
