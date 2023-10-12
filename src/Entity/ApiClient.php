<?php

namespace App\Entity;

use App\Constants;
use App\Repository\ApiClientRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    protected $identifier;

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

    public function __construct(string $name, string $identifier, ?string $secret)
    {
        parent::__construct($name, $identifier, $secret);
        $this->ownedEnrichments = new ArrayCollection();
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
        return $this->identifier;
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

    public function addOwnedEnrichments(Enrichment $enrichment): static
    {
        if (!$this->ownedEnrichments->contains($enrichment)) {
            $this->ownedEnrichments->add($enrichment);
            $enrichment->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOwnedEnrichments(Enrichment $enrichment): static
    {
        // set the owning side to null (unless already changed)
        if ($this->ownedEnrichments->removeElement($enrichment) && $enrichment->getCreatedBy() === $this) {
            $enrichment->setCreatedBy(null);
        }

        return $this;
    }
}