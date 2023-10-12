<?php

namespace App\Entity;

use App\Repository\ChoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChoiceRepository::class)]
class Choice implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une option.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $optionText = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(groups: ['enrichment_versions'])]
    private bool $correctAnswer = false;

    #[ORM\ManyToOne(inversedBy: 'choices', targetEntity: MultipleChoiceQuestion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?MultipleChoiceQuestion $multipleChoiceQuestion = null;

    public function __toString(): string
    {
        return $this->optionText;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOptionText(): ?string
    {
        return $this->optionText;
    }

    public function setOptionText(?string $optionText): self
    {
        $this->optionText = $optionText;

        return $this;
    }

    public function isCorrectAnswer(): bool
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(bool $correctAnswer): self
    {
        $this->correctAnswer = $correctAnswer;

        return $this;
    }

    public function getMultipleChoiceQuestion(): ?MultipleChoiceQuestion
    {
        return $this->multipleChoiceQuestion;
    }

    public function setMultipleChoiceQuestion(?MultipleChoiceQuestion $multipleChoiceQuestion): static
    {
        $this->multipleChoiceQuestion = $multipleChoiceQuestion;

        return $this;
    }
}