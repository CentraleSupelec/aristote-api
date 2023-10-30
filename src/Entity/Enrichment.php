<?php

namespace App\Entity;

use App\Repository\EnrichmentRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
    final public const STATUS_WAITING_MEIDA_UPLOAD = 'WAITING_MEIDA_UPLOAD';
    final public const STATUS_UPLOADING_MEDIA = 'UPLOADING_MEDIA';
    final public const STATUS_WAITING_MEDIA_TRANSCRIPTION = 'WAITING_MEDIA_TRANSCRIPTION';
    final public const STATUS_TRANSCRIBING_MEDIA = 'TRANSCRIBING_MEDIA';
    final public const STATUS_WAITING_AI_ENRICHMENT = 'WAITING_AI_ENRICHMENT';
    final public const STATUS_AI_ENRICHING = 'AI_ENRICHING';
    final public const STATUS_SUCCESS = 'SUCCESS';
    final public const STATUS_FAILURE = 'FAILURE';

    public static function getPossibleStatuses(): array
    {
        return [
            self::STATUS_WAITING_MEIDA_UPLOAD => self::STATUS_WAITING_MEIDA_UPLOAD,
            self::STATUS_UPLOADING_MEDIA => self::STATUS_UPLOADING_MEDIA,
            self::STATUS_WAITING_MEDIA_TRANSCRIPTION => self::STATUS_WAITING_MEDIA_TRANSCRIPTION,
            self::STATUS_TRANSCRIBING_MEDIA => self::STATUS_TRANSCRIBING_MEDIA,
            self::STATUS_WAITING_AI_ENRICHMENT => self::STATUS_WAITING_AI_ENRICHMENT,
            self::STATUS_AI_ENRICHING => self::STATUS_AI_ENRICHING,
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

    #[ORM\ManyToOne(inversedBy: 'aiProcessedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    private ?ApiClient $aiProcessedBy = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $aiProcessingTaskId = null;

    #[ORM\ManyToOne(inversedBy: 'transcribedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    private ?ApiClient $transcribedBy = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $transcriptionTaskId = null;

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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $failureCause = null;

    #[ORM\OneToOne(mappedBy: 'enrichment', targetEntity: Media::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private ?Media $media = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Url]
    private ?string $mediaUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Url]
    private ?string $notificationWebhookUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $notificationStatus = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'disciplines', description: 'Disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichments'])]
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected')]
    private ?array $disciplines = [];

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'mediaTypes', description: 'Meida Types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichments'])]
    #[Assert\Count(min: 1, minMessage: 'At least one media type is expected')]
    private ?array $mediaTypes = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEnrichmentStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEnrichmentEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $transribingStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $transribingEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?DateTimeInterface $notifiedAt = null;

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

    public function getAiProcessedBy(): ?ApiClient
    {
        return $this->aiProcessedBy;
    }

    public function setAiProcessedBy(?ApiClient $apiClient): static
    {
        $this->aiProcessedBy = $apiClient;

        return $this;
    }

    public function getTranscribedBy(): ?ApiClient
    {
        return $this->transcribedBy;
    }

    public function setTranscribedBy(?ApiClient $apiClient): static
    {
        $this->transcribedBy = $apiClient;

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

    public function getAiProcessingTaskId(): ?Uuid
    {
        return $this->aiProcessingTaskId;
    }

    public function setAiProcessingTaskId(?Uuid $aiProcessingTaskId): self
    {
        $this->aiProcessingTaskId = $aiProcessingTaskId;

        return $this;
    }

    public function getTranscriptionTaskId(): ?Uuid
    {
        return $this->transcriptionTaskId;
    }

    public function setTranscriptionTaskId(?Uuid $transcriptionTaskId): self
    {
        $this->transcriptionTaskId = $transcriptionTaskId;

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

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;

        // set the owning side of the relation if necessary
        if ($media instanceof Media && $media->getEnrichment() !== $this) {
            $media->setEnrichment($this);
        }

        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl): self
    {
        $this->mediaUrl = $mediaUrl;

        return $this;
    }

    public function getNotificationWebhookUrl(): ?string
    {
        return $this->notificationWebhookUrl;
    }

    public function setNotificationWebhookUrl(?string $notificationWebhookUrl): self
    {
        $this->notificationWebhookUrl = $notificationWebhookUrl;

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

    public function getAiEnrichmentStartedAt(): ?DateTimeInterface
    {
        return $this->aiEnrichmentStartedAt;
    }

    public function setAiEnrichmentStartedAt(DateTimeInterface $aiEnrichmentStartedAt): self
    {
        $this->aiEnrichmentStartedAt = $aiEnrichmentStartedAt;

        return $this;
    }

    public function getAiEnrichmentEndedAt(): ?DateTimeInterface
    {
        return $this->aiEnrichmentEndedAt;
    }

    public function setAiEnrichmentEndedAt(DateTimeInterface $aiEnrichmentEndedAt): self
    {
        $this->aiEnrichmentEndedAt = $aiEnrichmentEndedAt;

        return $this;
    }

    public function getTransribingStartedAt(): ?DateTimeInterface
    {
        return $this->transribingStartedAt;
    }

    public function setTransribingStartedAt(DateTimeInterface $transribingStartedAt): self
    {
        $this->transribingStartedAt = $transribingStartedAt;

        return $this;
    }

    public function getTransribingEndedAt(): ?DateTimeInterface
    {
        return $this->transribingEndedAt;
    }

    public function setTransribingEndedAt(DateTimeInterface $transribingEndedAt): self
    {
        $this->transribingEndedAt = $transribingEndedAt;

        return $this;
    }

    public function getNotifiedAt(): ?DateTimeInterface
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(DateTimeInterface $notifiedAt): self
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }

    public function getNotificationStatus(): ?string
    {
        return $this->notificationStatus;
    }

    public function setNotificationStatus(?string $notificationStatus): self
    {
        $this->notificationStatus = $notificationStatus;

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
}
