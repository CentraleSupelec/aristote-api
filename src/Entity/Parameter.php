<?php

namespace App\Entity;

use App\Constants;
use App\Repository\ParameterRepository;
use App\Validator\Constraints as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParameterRepository::class)]
#[AppAssert\ParameterConstraint()]
class Parameter implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Veuillez saisir le nom du paramètre', allowNull: false)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une description du paramètre', allowNull: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir la valeur du paramètre', allowNull: true)]
    private ?string $value = null;

    public function __toString(): string
    {
        return (string) $this->name;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function isDeletable(): bool
    {
        return !in_array($this->getName(), Constants::getMandatoryParameters());
    }
}
