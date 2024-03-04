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
    private Collection $apiClients;

    public function __construct()
    {
        $this->apiClients = new ArrayCollection();
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
    public function getApiClients(): Collection
    {
        return $this->apiClients;
    }

    public function addApiClient(ApiClient $apiClient): static
    {
        if (!$this->apiClients->contains($apiClient)) {
            $this->apiClients->add($apiClient);
            $apiClient->setInfrastructure($this);
        }

        return $this;
    }

    public function removeApiClient(ApiClient $apiClient): static
    {
        // set the owning side to null (unless already changed)
        if ($this->apiClients->removeElement($apiClient) && $apiClient->getInfrastructure() === $this) {
            $apiClient->setInfrastructure(null);
        }

        return $this;
    }
}
