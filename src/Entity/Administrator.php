<?php

namespace App\Entity;

use App\Constants;
use App\Repository\AdministratorRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Utilisateur déjà existant.')]
class Administrator implements Stringable, UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\Email(message: 'Veuillez saisir un email valide.')]
    #[Assert\NotBlank(message: 'Veuillez saisir un email.', allowNull: false)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $lastLoginAt = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: 'Veuillez saisir un mot de passe.', allowNull: false, groups: ['UpdateAdministrator'])]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    #[Assert\Count(min: 1, minMessage: 'Au moins un rôle doit être attribué à un administrateur')]
    private array $roles = [Constants::ROLE_SUPER_ADMIN];

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $enabled = false;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     */
    #[Assert\Length(min: 6, minMessage: 'Veuillez saisir un mot de passe plus long (minimum 6 caractères).')]
    #[Assert\NotBlank(message: 'Veuillez saisir un mot de passe.', allowNull: false, groups: ['CreateAdministrator'])]
    private ?string $plainPassword = null;

    public function __toString(): string
    {
        return $this->email ?? 'Administrator';
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLastLoginAt(): ?DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTime $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function eraseCredentials()
    {
        $this->setPlainPassword(null);
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = Constants::ROLE_SUPER_ADMIN;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
