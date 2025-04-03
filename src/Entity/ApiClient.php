<?php

namespace App\Entity;

use App\Constants;
use App\Repository\ApiClientRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\OAuth2Grants;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApiClientRepository::class)]
#[UniqueEntity(fields: ['identifier'], message: 'Un client avec cet idendifiant existe déjà.')]
class ApiClient extends AbstractClient implements ClientEntityInterface, Stringable, PasswordHasherAwareInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 80, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    protected string $identifier;

    #[Assert\Length(min: 6, minMessage: 'Veuillez saisir un mot de passe plus long (minimum 6 caractères).')]
    #[Assert\NotBlank(message: 'Veuillez saisir un secret.', allowNull: false, groups: ['CreateApiClient'])]
    private ?string $plainSecret = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $tokenLastRequestedAt = null;

    // Re-defined property to add constraint and avoid setter not being compatible with Collection forms (destructured array)
    #[Assert\Count(exactly: 1, exactMessage: 'Seul le grant type "client_credentials" peut être attribué au client api.')]
    #[Assert\Choice(callback: [Constants::class, 'getAvailableGrants'], multiple: true)]
    private array $formExposedGrants = [Constants::SCOPE_DEFAULT];

    // Re-defined property to add constraint and avoid setter not being compatible with Collection forms (destructured array)
    #[Assert\Count(min: 1, minMessage: 'Vous devez attribuer au moins un scope à ce client.')]
    #[Assert\Choice(callback: [Constants::class, 'getAvailableScopes'], multiple: true)]
    private array $formExposedScopes = [OAuth2Grants::CLIENT_CREDENTIALS];

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Enrichment::class)]
    private Collection $ownedEnrichments;

    #[ORM\OneToMany(mappedBy: 'aiProcessedBy', targetEntity: Enrichment::class)]
    private Collection $aiProcessedEnrichments;

    #[ORM\OneToMany(mappedBy: 'transcribedBy', targetEntity: Enrichment::class)]
    private Collection $transcribedEnrichments;

    #[ORM\OneToMany(mappedBy: 'aiEvaluatedBy', targetEntity: Enrichment::class)]
    private Collection $aiEvaluatedEnrichments;

    #[ORM\OneToMany(mappedBy: 'translatedBy', targetEntity: Enrichment::class)]
    private Collection $translatedEnrichments;

    // For Workers
    #[ORM\ManyToOne(inversedBy: 'workers', targetEntity: AiModel::class, cascade: ['persist'])]
    private ?AiModel $aiModel = null;

    // For Workers
    #[ORM\ManyToOne(inversedBy: 'workers', targetEntity: Infrastructure::class, cascade: ['persist'])]
    private ?Infrastructure $infrastructure = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedTranscriptionApiClients', targetEntity: AiModel::class, cascade: ['persist'])]
    private ?AiModel $transcriptionModel = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedTranscriptionApiClients', targetEntity: Infrastructure::class, cascade: ['persist'])]
    private ?Infrastructure $transcriptionInfrastructure = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedTranslationApiClients', targetEntity: AiModel::class, cascade: ['persist'])]
    private ?AiModel $translationModel = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedTranslationApiClients', targetEntity: Infrastructure::class, cascade: ['persist'])]
    private ?Infrastructure $translationInfrastructure = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedEnrichmentApiClients', targetEntity: AiModel::class, cascade: ['persist'])]
    private ?AiModel $enrichmentModel = null;

    // For Clients
    #[ORM\ManyToOne(inversedBy: 'forcedEnrichmentApiClients', targetEntity: Infrastructure::class, cascade: ['persist'])]
    private ?Infrastructure $enrichmentInfrastructure = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false], nullable: true)]
    private bool $treatUnspecifiedModelOrInfrastructure = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $jobLastRequestedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $jobLastTakendAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $jobLastSuccessAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $jobLastFailuredAt = null;

    public function __construct(string $name, string $identifier, ?string $secret)
    {
        parent::__construct($name, $identifier, $secret);
        $this->ownedEnrichments = new ArrayCollection();
        $this->aiProcessedEnrichments = new ArrayCollection();
        $this->transcribedEnrichments = new ArrayCollection();
        $this->aiEvaluatedEnrichments = new ArrayCollection();
        $this->translatedEnrichments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->identifier ?? 'API Client';
    }

    public function getPasswordHasherName(): ?string
    {
        return self::class;
    }

    /**
     * @return string[]
     */
    public function getRedirectUri(): array
    {
        return $this->getRedirectUris();
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->identifier ?? null;
    }

    public function getPlainSecret(): ?string
    {
        return $this->plainSecret;
    }

    public function setPlainSecret(?string $plainSecret): self
    {
        $this->plainSecret = $plainSecret;

        return $this;
    }

    public function eraseCredentials()
    {
        $this->setPlainSecret(null);
    }

    public function isConfidential(): bool
    {
        return true;
    }

    public function getTokenLastRequestedAt(): ?DateTimeInterface
    {
        return $this->tokenLastRequestedAt;
    }

    public function setTokenLastRequestedAt(?DateTimeInterface $datetime): self
    {
        $this->tokenLastRequestedAt = $datetime;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFormExposedGrants(): array
    {
        $this->formExposedGrants = array_unique(array_map(
            fn (string|Grant $grant) => (string) $grant, $this->getGrants()
        ));

        return $this->formExposedGrants;
    }

    public function setFormExposedGrants(array $formExposedGrants): self
    {
        $this->formExposedGrants = $formExposedGrants;

        $this->setGrants(...array_unique(array_map(
            fn (string $grant) => new Grant($grant), $formExposedGrants
        )));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFormExposedScopes(): array
    {
        $this->formExposedScopes = array_unique(array_map(
            fn (string|Scope $scope) => (string) $scope, $this->getScopes()
        ));

        return $this->formExposedScopes;
    }

    public function setFormExposedScopes(array $formExposedScopes): self
    {
        $this->formExposedScopes = $formExposedScopes;

        $this->setScopes(...array_unique(array_map(
            fn (string $scope) => new Scope($scope), $formExposedScopes
        )));

        return $this;
    }

    /**
     * @return Collection<int, Enrichment>
     */
    public function getOwnedEnrichments(): Collection
    {
        return $this->ownedEnrichments;
    }

    public function addOwnedEnrichment(Enrichment $enrichment): static
    {
        if (!$this->ownedEnrichments->contains($enrichment)) {
            $this->ownedEnrichments->add($enrichment);
            $enrichment->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOwnedEnrichment(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->ownedEnrichments->removeElement($enrichment) && $enrichment->getCreatedBy() === $this) {
            $enrichment->setCreatedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrichment>
     */
    public function getAiProcessedEnrichments(): Collection
    {
        return $this->aiProcessedEnrichments;
    }

    public function addAiProcessedEnrichment(Enrichment $enrichment): static
    {
        if (!$this->aiProcessedEnrichments->contains($enrichment)) {
            $this->aiProcessedEnrichments->add($enrichment);
            $enrichment->setAiProcessedBy($this);
        }

        return $this;
    }

    public function removeAiProcessedEnrichment(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->aiProcessedEnrichments->removeElement($enrichment) && $enrichment->getAiProcessedBy() === $this) {
            $enrichment->setAiProcessedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrichment>
     */
    public function getTranscribedEnrichments(): Collection
    {
        return $this->transcribedEnrichments;
    }

    public function addTranscribedEnrichment(Enrichment $enrichment): static
    {
        if (!$this->transcribedEnrichments->contains($enrichment)) {
            $this->transcribedEnrichments->add($enrichment);
            $enrichment->setTranscribedBy($this);
        }

        return $this;
    }

    public function removeTranscribedEnrichment(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->transcribedEnrichments->removeElement($enrichment) && $enrichment->getTranscribedBy() === $this) {
            $enrichment->setTranscribedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrichment>
     */
    public function getAiEvaluatedEnrichments(): Collection
    {
        return $this->aiEvaluatedEnrichments;
    }

    public function addAiEvaluatedEnrichment(Enrichment $enrichment): static
    {
        if (!$this->aiEvaluatedEnrichments->contains($enrichment)) {
            $this->aiEvaluatedEnrichments->add($enrichment);
            $enrichment->setAiEvaluatedBy($this);
        }

        return $this;
    }

    public function removeAiEvaluatedEnrichment(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->aiEvaluatedEnrichments->removeElement($enrichment) && $enrichment->getAiEvaluatedBy() === $this) {
            $enrichment->setAiEvaluatedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrichment>
     */
    public function getTranslatedEnrichments(): Collection
    {
        return $this->translatedEnrichments;
    }

    public function addTranslatedEnrichment(Enrichment $enrichment): static
    {
        if (!$this->translatedEnrichments->contains($enrichment)) {
            $this->translatedEnrichments->add($enrichment);
            $enrichment->setTranslatedBy($this);
        }

        return $this;
    }

    public function removeTranslatedEnrichment(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->translatedEnrichments->removeElement($enrichment) && $enrichment->getTranslatedBy() === $this) {
            $enrichment->setTranslatedBy(null);
        }

        return $this;
    }

    public function getAiModel(): ?AiModel
    {
        return $this->aiModel;
    }

    public function setAiModel(?AiModel $aiModel): static
    {
        $this->aiModel = $aiModel;

        return $this;
    }

    public function getInfrastructure(): ?Infrastructure
    {
        return $this->infrastructure;
    }

    public function setInfrastructure(?Infrastructure $infrastructure): static
    {
        $this->infrastructure = $infrastructure;

        return $this;
    }

    public function getTranscriptionModel(): ?AiModel
    {
        return $this->transcriptionModel;
    }

    public function setTranscriptionModel(?AiModel $transcriptionModel): static
    {
        $this->transcriptionModel = $transcriptionModel;

        if ($transcriptionModel instanceof AiModel) {
            $transcriptionModel->addForcedTranscriptionApiClient($this);
        }

        return $this;
    }

    public function getTranscriptionInfrastructure(): ?Infrastructure
    {
        return $this->transcriptionInfrastructure;
    }

    public function setTranscriptionInfrastructure(?Infrastructure $transcriptionInfrastructure): static
    {
        $this->transcriptionInfrastructure = $transcriptionInfrastructure;

        if ($transcriptionInfrastructure instanceof Infrastructure) {
            $transcriptionInfrastructure->addForcedTranscriptionApiClient($this);
        }

        return $this;
    }

    public function getTranslationModel(): ?AiModel
    {
        return $this->translationModel;
    }

    public function setTranslationModel(?AiModel $translationModel): static
    {
        $this->translationModel = $translationModel;

        if ($translationModel instanceof AiModel) {
            $translationModel->addForcedTranslationApiClient($this);
        }

        return $this;
    }

    public function getTranslationInfrastructure(): ?Infrastructure
    {
        return $this->translationInfrastructure;
    }

    public function setTranslationInfrastructure(?Infrastructure $translationInfrastructure): static
    {
        $this->translationInfrastructure = $translationInfrastructure;

        if ($translationInfrastructure instanceof Infrastructure) {
            $translationInfrastructure->addForcedTranslationApiClient($this);
        }

        return $this;
    }

    public function getEnrichmentModel(): ?AiModel
    {
        return $this->enrichmentModel;
    }

    public function setEnrichmentModel(?AiModel $enrichmentModel): static
    {
        $this->enrichmentModel = $enrichmentModel;

        if ($enrichmentModel instanceof AiModel) {
            $enrichmentModel->addForcedEnrichmentApiClient($this);
        }

        return $this;
    }

    public function getEnrichmentInfrastructure(): ?Infrastructure
    {
        return $this->enrichmentInfrastructure;
    }

    public function setEnrichmentInfrastructure(?Infrastructure $enrichmentInfrastructure): static
    {
        $this->enrichmentInfrastructure = $enrichmentInfrastructure;

        if ($enrichmentInfrastructure instanceof Infrastructure) {
            $enrichmentInfrastructure->addForcedEnrichmentApiClient($this);
        }

        return $this;
    }

    public function getTreatUnspecifiedModelOrInfrastructure(): bool
    {
        return $this->treatUnspecifiedModelOrInfrastructure;
    }

    public function setTreatUnspecifiedModelOrInfrastructure(bool $treatUnspecifiedModelOrInfrastructure): self
    {
        $this->treatUnspecifiedModelOrInfrastructure = $treatUnspecifiedModelOrInfrastructure;

        return $this;
    }

    public function getJobLastRequestedAt(): ?DateTimeInterface
    {
        return $this->jobLastRequestedAt;
    }

    public function setJobLastRequestedAt(DateTimeInterface $jobLastRequestedAt): self
    {
        $this->jobLastRequestedAt = $jobLastRequestedAt;

        return $this;
    }

    public function getJobLastTakendAt(): ?DateTimeInterface
    {
        return $this->jobLastTakendAt;
    }

    public function setJobLastTakendAt(DateTimeInterface $jobLastTakendAt): self
    {
        $this->jobLastTakendAt = $jobLastTakendAt;

        return $this;
    }

    public function getJobLastSuccessAt(): ?DateTimeInterface
    {
        return $this->jobLastSuccessAt;
    }

    public function setJobLastSuccessAt(DateTimeInterface $jobLastSuccessAt): self
    {
        $this->jobLastSuccessAt = $jobLastSuccessAt;

        return $this;
    }

    public function getJobLastFailuredAt(): ?DateTimeInterface
    {
        return $this->jobLastFailuredAt;
    }

    public function setJobLastFailuredAt(DateTimeInterface $jobLastFailuredAt): self
    {
        $this->jobLastFailuredAt = $jobLastFailuredAt;

        return $this;
    }
}
