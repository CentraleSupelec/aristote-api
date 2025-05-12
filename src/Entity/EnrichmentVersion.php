<?php

namespace App\Entity;

use App\Repository\EnrichmentVersionRepository;
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

#[ORM\Entity(repositoryClass: EnrichmentVersionRepository::class)]
class EnrichmentVersion
{
    use TimestampableEntity;

    public static function getSortFields(): array
    {
        return [
            'createdAt',
            'updatedAt',
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
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    #[Assert\Valid]
    private ?EnrichmentVersionMetadata $enrichmentVersionMetadata = null;

    #[ORM\OneToOne(mappedBy: 'enrichmentVersion', targetEntity: Transcript::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions_with_transcript'])]
    private ?Transcript $transcript = null;

    #[ORM\OneToMany(mappedBy: 'enrichmentVersion', targetEntity: MultipleChoiceQuestion::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $multipleChoiceQuestions;

    #[ORM\ManyToOne(inversedBy: 'versions', targetEntity: Enrichment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enrichment $enrichment = null;

    #[ORM\OneToOne(inversedBy: 'initialEnrichmentVersion', targetEntity: Enrichment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enrichment $initialEnrichmentVersionOf = null;

    #[ORM\OneToOne(inversedBy: 'lastEnrichmentVersion', targetEntity: Enrichment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enrichment $lastEnrichmentVersionOf = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(groups: ['enrichment_versions'])]
    private bool $initialVersion = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?DateTimeInterface $lastEvaluationDate = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(groups: ['enrichment_versions'])]
    private bool $aiGenerated = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'disciplines', description: 'Disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_versions'])]
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected', groups: ['metadata'])]
    private ?array $disciplines = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(property: 'mediaTypes', description: 'Meida Types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Groups(groups: ['enrichment_versions'])]
    #[Assert\Count(min: 1, minMessage: 'At least one media type is expected', groups: ['metadata'])]
    private ?array $mediaTypes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $endUserIdentifier = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $aiEvaluation = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $aiModel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $infrastructure = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Url(requireTld: true)]
    private ?string $notificationWebhookUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $language = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $translateTo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $translatedNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEnrichmentStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEnrichmentEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $transcribingStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $transcribingEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $translationStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $translationEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEvaluationStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $aiEvaluationEndedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $notifiedAt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $notificationStatus = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 3, nullable: true)]
    private ?string $evaluationMark = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $generateMetadata = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $generateQuiz = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $generateNotes = false;

    #[ORM\ManyToOne(inversedBy: 'aiProcessedEnrichments', targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[OA\Property(property: 'aiProcessedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $aiProcessedBy = null;

    #[ORM\ManyToOne(inversedBy: 'aiEvaluatedEnrichmentVersions', targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[OA\Property(property: 'aiEvaluatedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $aiEvaluatedBy = null;

    #[ORM\ManyToOne(inversedBy: 'transcribedEnrichmentVersions', targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[OA\Property(property: 'transcribedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $transcribedBy = null;

    #[ORM\ManyToOne(inversedBy: 'translatedEnrichmentVersions', targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: true, referencedColumnName: 'identifier')]
    #[OA\Property(property: 'translatedBy', type: 'object', properties: [
        new OA\Property(
            property: 'name',
            type: 'string'
        ),
    ])]
    private ?ApiClient $translatedBy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $failureCause = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $enrichmentRetries = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $translationRetries = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $evaluationRetries = 0;

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

        if ($enrichmentVersionMetadata instanceof EnrichmentVersionMetadata) {
            $enrichmentVersionMetadata->setEnrichmentVersion($this);
        }

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

    public function getInitialEnrichmentVersionOf(): ?Enrichment
    {
        return $this->initialEnrichmentVersionOf;
    }

    public function setInitialEnrichmentVersionOf(?Enrichment $initialEnrichmentVersionOf): static
    {
        $this->initialEnrichmentVersionOf = $initialEnrichmentVersionOf;

        return $this;
    }

    public function getLastEnrichmentVersionOf(): ?Enrichment
    {
        return $this->initialEnrichmentVersionOf;
    }

    public function setLastEnrichmentVersionOf(?Enrichment $lastEnrichmentVersionOf): static
    {
        $this->lastEnrichmentVersionOf = $lastEnrichmentVersionOf;

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

    public function getLastEvaluationDate(): ?DateTimeInterface
    {
        return $this->lastEvaluationDate;
    }

    public function setLastEvaluationDate(DateTimeInterface $lastEvaluationDate): self
    {
        $this->lastEvaluationDate = $lastEvaluationDate;

        return $this;
    }

    public function isAiGenerated(): bool
    {
        return $this->aiGenerated;
    }

    public function setAiGenerated(bool $aiGenerated): self
    {
        $this->aiGenerated = $aiGenerated;

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

    public function getNotificationWebhookUrl(): ?string
    {
        return $this->notificationWebhookUrl;
    }

    public function setNotificationWebhookUrl(?string $notificationWebhookUrl): self
    {
        $this->notificationWebhookUrl = $notificationWebhookUrl;

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

    public function getAiEvaluation(): ?string
    {
        return $this->aiEvaluation;
    }

    public function setAiEvaluation(?string $aiEvaluation): self
    {
        $this->aiEvaluation = $aiEvaluation;

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

    public function getTranscribingStartedAt(): ?DateTimeInterface
    {
        return $this->transcribingStartedAt;
    }

    public function setTranscribingStartedAt(?DateTimeInterface $transcribingStartedAt): self
    {
        $this->transcribingStartedAt = $transcribingStartedAt;

        return $this;
    }

    public function getTranscribingEndedAt(): ?DateTimeInterface
    {
        return $this->transcribingEndedAt;
    }

    public function setTranscribingEndedAt(?DateTimeInterface $transcribingEndedAt): self
    {
        $this->transcribingEndedAt = $transcribingEndedAt;

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

    public function setNotifiedAt(?DateTimeInterface $notifiedAt): self
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

    public function getFailureCause(): ?string
    {
        return $this->failureCause;
    }

    public function setFailureCause(?string $failureCause): self
    {
        $this->failureCause = $failureCause;

        return $this;
    }

    public function getEnrichmentRetries(): int
    {
        return $this->enrichmentRetries;
    }

    public function setEnrichmentRetries(int $enrichmentRetries): self
    {
        $this->enrichmentRetries = $enrichmentRetries;

        return $this;
    }

    public function getTranslationRetries(): int
    {
        return $this->translationRetries;
    }

    public function setTranslationRetries(int $translationRetries): self
    {
        $this->translationRetries = $translationRetries;

        return $this;
    }

    public function getEvaluationRetries(): int
    {
        return $this->evaluationRetries;
    }

    public function setEvaluationRetries(int $evaluationRetries): self
    {
        $this->evaluationRetries = $evaluationRetries;

        return $this;
    }
}
