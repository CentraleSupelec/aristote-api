<?php

namespace App\Entity;

use App\Repository\EnrichmentRepository;
use App\Validator\Constraints as AppAssert;
use DateTimeInterface;
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

#[ORM\Entity(repositoryClass: EnrichmentRepository::class)]
#[AppAssert\TranslationConstraint()]
class Enrichment
{
    use TimestampableEntity;
    final public const STATUS_WAITING_MEIDA_UPLOAD = 'WAITING_MEIDA_UPLOAD';
    final public const STATUS_UPLOADING_MEDIA = 'UPLOADING_MEDIA';
    final public const STATUS_WAITING_MEDIA_TRANSCRIPTION = 'WAITING_MEDIA_TRANSCRIPTION';
    final public const STATUS_TRANSCRIBING_MEDIA = 'TRANSCRIBING_MEDIA';
    final public const STATUS_WAITING_AI_ENRICHMENT = 'WAITING_AI_ENRICHMENT';
    final public const STATUS_AI_ENRICHING = 'AI_ENRICHING';
    final public const STATUS_WAITING_TRANSLATION = 'WAITING_TRANSLATION';
    final public const STATUS_TRANSLATING = 'TRANSLATING';
    final public const STATUS_WAITING_AI_EVALUATION = 'WAITING_AI_EVALUATION';
    final public const STATUS_AI_EVALUATING = 'AI_EVALUATING';
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
            self::STATUS_WAITING_TRANSLATION => self::STATUS_WAITING_TRANSLATION,
            self::STATUS_TRANSLATING => self::STATUS_TRANSLATING,
            self::STATUS_WAITING_AI_EVALUATION => self::STATUS_WAITING_AI_EVALUATION,
            self::STATUS_AI_EVALUATING => self::STATUS_AI_EVALUATING,
            self::STATUS_SUCCESS => self::STATUS_SUCCESS,
            self::STATUS_FAILURE => self::STATUS_FAILURE,
        ];
    }

    final public const LANGUAGE_FR = 'fr';
    final public const LANGUAGE_EN = 'en';

    public static function getSupportedLanguages(): array
    {
        return [
            self::LANGUAGE_FR,
            self::LANGUAGE_EN,
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['enrichments'])]
    protected $createdAt;

    #[ORM\ManyToOne(inversedBy: 'ownedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'identifier')]
    private ?ApiClient $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'aiProcessedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[Groups(groups: ['enrichments_with_status'])]
    #[OA\Property(property: 'aiProcessedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $aiProcessedBy = null;

    #[ORM\ManyToOne(inversedBy: 'aiEvaluatedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[Groups(groups: ['enrichments_with_status'])]
    #[OA\Property(property: 'aiEvaluatedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $aiEvaluatedBy = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $aiProcessingTaskId = null;

    #[ORM\ManyToOne(inversedBy: 'transcribedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[Groups(groups: ['enrichments_with_status'])]
    #[OA\Property(property: 'transcribedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $transcribedBy = null;

    #[ORM\ManyToOne(inversedBy: 'translatedEnrichments', targetEntity: ApiClient::class, )]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[Groups(groups: ['enrichments_with_status'])]
    #[OA\Property(property: 'translatedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $translatedBy = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $transcriptionTaskId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $aiEvaluationTaskId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $translationTaskId = null;

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
    #[Groups(groups: ['enrichments'])]
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
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected', groups: ['metadata'])]
    private ?array $disciplines = [];

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'mediaTypes', description: 'Meida Types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichments'])]
    #[Assert\Count(min: 1, minMessage: 'At least one media type is expected', groups: ['metadata'])]
    private ?array $mediaTypes = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $latestEnrichmentRequestedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $uploadStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $uploadEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $aiEnrichmentStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $aiEnrichmentEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $transribingStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $transribingEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $translationStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $translationEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?DateTimeInterface $notifiedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $aiEvaluation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $aiEvaluationStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichments_with_status'])]
    private ?DateTimeInterface $aiEvaluationEndedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $endUserIdentifier = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[OA\Property(property: 'contributors', description: 'Contributors', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichments'])]
    private ?array $contributors = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $aiModel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $infrastructure = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(groups: ['enrichments'])]
    private int $retries = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $priority = 1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Choice(callback: [self::class, 'getSupportedLanguages'], multiple: false, message: "The chosen language is not supported. Supported languages are : ['fr', 'en']")]
    #[Groups(groups: ['enrichments'])]
    private ?string $language = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Choice(callback: [self::class, 'getSupportedLanguages'], multiple: false, message: "The chosen language to translate to is not supported. Supported languages are : ['fr', 'en']")]
    #[Groups(groups: ['enrichments'])]
    private ?string $translateTo = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $deleted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(groups: ['enrichments'])]
    private int $aiGenerationCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 3, nullable: true)]
    private ?string $evaluationMark = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $generateMetadata = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $generateQuiz = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $generateNotes = false;

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

    public function getAiEvaluatedBy(): ?ApiClient
    {
        return $this->aiEvaluatedBy;
    }

    public function setAiEvaluatedBy(?ApiClient $apiClient): static
    {
        $this->aiEvaluatedBy = $apiClient;

        return $this;
    }

    public function getTranslatedBy(): ?ApiClient
    {
        return $this->translatedBy;
    }

    public function setTranslatedBy(?ApiClient $apiClient): static
    {
        $this->translatedBy = $apiClient;

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

    public function getAiEvaluationTaskId(): ?Uuid
    {
        return $this->aiEvaluationTaskId;
    }

    public function setAiEvaluationTaskId(?Uuid $aiEvaluationTaskId): self
    {
        $this->aiEvaluationTaskId = $aiEvaluationTaskId;

        return $this;
    }

    public function getTranslationTaskId(): ?Uuid
    {
        return $this->translationTaskId;
    }

    public function setTranslationTaskId(?Uuid $translationTaskId): self
    {
        $this->translationTaskId = $translationTaskId;

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

    public function getLatestEnrichmentRequestedAt(): ?DateTimeInterface
    {
        return $this->latestEnrichmentRequestedAt;
    }

    public function setLatestEnrichmentRequestedAt(?DateTimeInterface $latestEnrichmentRequestedAt): self
    {
        $this->latestEnrichmentRequestedAt = $latestEnrichmentRequestedAt;

        return $this;
    }

    public function getUploadStartedAt(): ?DateTimeInterface
    {
        return $this->uploadStartedAt;
    }

    public function setUploadStartedAt(?DateTimeInterface $uploadStartedAt): self
    {
        $this->uploadStartedAt = $uploadStartedAt;

        return $this;
    }

    public function getUploadEndedAt(): ?DateTimeInterface
    {
        return $this->uploadEndedAt;
    }

    public function setUploadEndedAt(?DateTimeInterface $uploadEndedAt): self
    {
        $this->uploadEndedAt = $uploadEndedAt;

        return $this;
    }

    public function getAiEnrichmentStartedAt(): ?DateTimeInterface
    {
        return $this->aiEnrichmentStartedAt;
    }

    public function setAiEnrichmentStartedAt(?DateTimeInterface $aiEnrichmentStartedAt): self
    {
        $this->aiEnrichmentStartedAt = $aiEnrichmentStartedAt;

        return $this;
    }

    public function getAiEnrichmentEndedAt(): ?DateTimeInterface
    {
        return $this->aiEnrichmentEndedAt;
    }

    public function setAiEnrichmentEndedAt(?DateTimeInterface $aiEnrichmentEndedAt): self
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

    public function getTranslationStartedAt(): ?DateTimeInterface
    {
        return $this->translationStartedAt;
    }

    public function setTranslationStartedAt(?DateTimeInterface $translationStartedAt): self
    {
        $this->translationStartedAt = $translationStartedAt;

        return $this;
    }

    public function getTranslationEndedAt(): ?DateTimeInterface
    {
        return $this->translationEndedAt;
    }

    public function setTranslationEndedAt(?DateTimeInterface $translationEndedAt): self
    {
        $this->translationEndedAt = $translationEndedAt;

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

    public function getMediaTypes(): ?array
    {
        return $this->mediaTypes;
    }

    public function setMediaTypes(?array $mediaTypes): self
    {
        $this->mediaTypes = $mediaTypes;

        return $this;
    }

    public function getDisciplines(): ?array
    {
        return $this->disciplines;
    }

    public function setDisciplines(?array $disciplines): self
    {
        $this->disciplines = $disciplines;

        return $this;
    }

    public function getAiEvaluation(): ?string
    {
        return $this->aiEvaluation;
    }

    public function setAiEvaluation(?string $aiEvaluation): self
    {
        $this->aiEvaluation = $aiEvaluation;

        return $this;
    }

    public function getAiEvaluationStartedAt(): ?DateTimeInterface
    {
        return $this->aiEvaluationStartedAt;
    }

    public function setAiEvaluationStartedAt(?DateTimeInterface $aiEvaluationStartedAt): self
    {
        $this->aiEvaluationStartedAt = $aiEvaluationStartedAt;

        return $this;
    }

    public function getAiEvaluationEndedAt(): ?DateTimeInterface
    {
        return $this->aiEvaluationEndedAt;
    }

    public function setAiEvaluationEndedAt(?DateTimeInterface $aiEvaluationEndedAt): self
    {
        $this->aiEvaluationEndedAt = $aiEvaluationEndedAt;

        return $this;
    }

    public function getEndUserIdentifier(): ?string
    {
        return $this->endUserIdentifier;
    }

    public function setEndUserIdentifier(?string $endUserIdentifier): self
    {
        $this->endUserIdentifier = $endUserIdentifier;

        return $this;
    }

    public function getContributors(): ?array
    {
        return $this->contributors;
    }

    public function setContributors(?array $contributors): self
    {
        $this->contributors = $contributors;

        return $this;
    }

    public function getAiModel(): ?string
    {
        return $this->aiModel;
    }

    public function setAiModel(?string $aiModel): self
    {
        $this->aiModel = $aiModel;

        return $this;
    }

    public function getInfrastructure(): ?string
    {
        return $this->infrastructure;
    }

    public function setInfrastructure(?string $infrastructure): self
    {
        $this->infrastructure = $infrastructure;

        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getTranslateTo(): ?string
    {
        return $this->translateTo;
    }

    public function setTranslateTo(?string $translateTo): self
    {
        $this->translateTo = $translateTo;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getAiGenerationCount(): int
    {
        return $this->aiGenerationCount;
    }

    public function setAiGenerationCount(int $aiGenerationCount): self
    {
        $this->aiGenerationCount = $aiGenerationCount;

        return $this;
    }

    public function getEvaluationMark(): ?string
    {
        return $this->evaluationMark;
    }

    public function setEvaluationMark(?string $evaluationMark): self
    {
        $this->evaluationMark = $evaluationMark;

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
