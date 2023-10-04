<?php

namespace App\Entity;

use App\Repository\MultipleChoiceQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MultipleChoiceQuestionRepository::class)]
class MultipleChoiceQuestion implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une question.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $question = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une explication.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $explanation = null;

    #[ORM\OneToMany(mappedBy: 'multipleChoiceQuestion', targetEntity: Choice::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions'])]
    private Collection $choices;

    #[ORM\ManyToOne(inversedBy: 'multipleChoiceQuestions', targetEntity: EnrichmentVersion::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?EnrichmentVersion $enrichmentVersion = null;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->question;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(?string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): self
    {
        $this->explanation = $explanation;

        return $this;
    }

    /**
     * @return Collection<int, Choice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(Choice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setMultipleChoiceQuestion($this);
        }

        return $this;
    }

    public function removeChoice(Choice $choice): static
    {
        // set the owning side to null (unless already changed)
        if ($this->choices->removeElement($choice) && $choice->getMultipleChoiceQuestion() === $this) {
            $choice->setMultipleChoiceQuestion(null);
        }

        return $this;
    }

    public function getEnrichmentVersion(): ?EnrichmentVersion
    {
        return $this->enrichmentVersion;
    }

    public function setEnrichmentVersion(?EnrichmentVersion $enrichmentVersion): static
    {
        $this->enrichmentVersion = $enrichmentVersion;

        return $this;
    }
}
