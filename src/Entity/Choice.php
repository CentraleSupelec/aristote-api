<?php

namespace App\Entity;

use App\Repository\ChoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
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
    #[OA\Property(property: 'id', description: 'Choice ID', type: 'string')]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'translation_job', 'translation_post', 'enrichment_version_evaluation'])]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'text', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une option.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post', 'ai_evaluation_job', 'translation_job', 'enrichment_version_creation', 'translation_post'])]
    private ?string $optionText = null;

    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez saisir une option.', allowNull: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $translatedOptionText = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post', 'ai_evaluation_job', 'enrichment_version_creation'])]
    private bool $correctAnswer = false;

    #[ORM\ManyToOne(inversedBy: 'choices', targetEntity: MultipleChoiceQuestion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?MultipleChoiceQuestion $multipleChoiceQuestion = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_version_evaluation'])]
    private ?bool $thumbUp = null;

    public function __toString(): string
    {
        return $this->optionText;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getTranslatedOptionText(): ?string
    {
        return $this->translatedOptionText;
    }

    public function setTranslatedOptionText(?string $translatedOptionText): self
    {
        $this->translatedOptionText = $translatedOptionText;

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

    public function getThumbUp(): ?bool
    {
        return $this->thumbUp;
    }

    public function setThumbUp(?bool $thumbUp): self
    {
        $this->thumbUp = $thumbUp;

        return $this;
    }
}
