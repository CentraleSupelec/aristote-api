<?php

namespace App\Entity;

use App\Repository\InfrastructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InfrastructureRepository::class)]
class Infrastructure implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text', length: 255)]
    #[Assert\NotBlank(message: "Veuillez saisir un nom d'infrastructure.", allowNull: false)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'infrastructure', targetEntity: ApiClient::class)]
    private Collection $workers;

    #[ORM\OneToMany(mappedBy: 'transcriptionInfrastructure', targetEntity: ApiClient::class)]
    private Collection $forcedTranscriptionApiClients;

    #[ORM\OneToMany(mappedBy: 'translationInfrastructure', targetEntity: ApiClient::class)]
    private Collection $forcedTranslationApiClients;

    #[ORM\OneToMany(mappedBy: 'enrichmentInfrastructure', targetEntity: ApiClient::class)]
    private Collection $forcedEnrichmentApiClients;

    public function __construct()
    {
        $this->workers = new ArrayCollection();
        $this->forcedTranscriptionApiClients = new ArrayCollection();
        $this->forcedTranslationApiClients = new ArrayCollection();
        $this->forcedEnrichmentApiClients = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ApiClient>
     */
    public function getWorkers(): Collection
    {
        return $this->workers;
    }

    public function addWorker(ApiClient $worker): static
    {
        if (!$this->workers->contains($worker)) {
            $this->workers->add($worker);
            $worker->setInfrastructure($this);
        }

        return $this;
    }

    public function removeWorker(ApiClient $worker): static
    {
        // set the owning side to null (unless already changed)
        if ($this->workers->removeElement($worker) && $worker->getInfrastructure() === $this) {
            $worker->setInfrastructure(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiClient>
     */
    public function getForcedTranscriptionApiClients(): Collection
    {
        return $this->forcedTranscriptionApiClients;
    }

    public function addForcedTranscriptionApiClient(ApiClient $forcedTranscriptionApiClient): static
    {
        if (!$this->forcedTranscriptionApiClients->contains($forcedTranscriptionApiClient)) {
            $this->forcedTranscriptionApiClients->add($forcedTranscriptionApiClient);
            $forcedTranscriptionApiClient->setInfrastructure($this);
        }

        return $this;
    }

    public function removeForcedTranscriptionApiClient(ApiClient $forcedTranscriptionApiClient): static
    {
        // set the owning side to null (unless already changed)
        if ($this->forcedTranscriptionApiClients->removeElement($forcedTranscriptionApiClient) && $forcedTranscriptionApiClient->getInfrastructure() === $this) {
            $forcedTranscriptionApiClient->setInfrastructure(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiClient>
     */
    public function getForcedTranslationApiClients(): Collection
    {
        return $this->forcedTranslationApiClients;
    }

    public function addForcedTranslationApiClient(ApiClient $forcedTranslationApiClient): static
    {
        if (!$this->forcedTranslationApiClients->contains($forcedTranslationApiClient)) {
            $this->forcedTranslationApiClients->add($forcedTranslationApiClient);
            $forcedTranslationApiClient->setInfrastructure($this);
        }

        return $this;
    }

    public function removeForcedTranslationApiClient(ApiClient $forcedTranslationApiClient): static
    {
        // set the owning side to null (unless already changed)
        if ($this->forcedTranslationApiClients->removeElement($forcedTranslationApiClient) && $forcedTranslationApiClient->getInfrastructure() === $this) {
            $forcedTranslationApiClient->setInfrastructure(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiClient>
     */
    public function getForcedEnrichmentApiClients(): Collection
    {
        return $this->forcedEnrichmentApiClients;
    }

    public function addForcedEnrichmentApiClient(ApiClient $forcedEnrichmentApiClient): static
    {
        if (!$this->forcedEnrichmentApiClients->contains($forcedEnrichmentApiClient)) {
            $this->forcedEnrichmentApiClients->add($forcedEnrichmentApiClient);
            $forcedEnrichmentApiClient->setInfrastructure($this);
        }

        return $this;
    }

    public function removeForcedEnrichmentApiClient(ApiClient $forcedEnrichmentApiClient): static
    {
        // set the owning side to null (unless already changed)
        if ($this->forcedEnrichmentApiClients->removeElement($forcedEnrichmentApiClient) && $forcedEnrichmentApiClient->getInfrastructure() === $this) {
            $forcedEnrichmentApiClient->setInfrastructure(null);
        }

        return $this;
    }
}
