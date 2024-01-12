<?php

namespace App\Entity;

use App\Repository\MultipleChoiceQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MultipleChoiceQuestionRepository::class)]
// #[ORM\OrderBy(['createdAt' => 'ASC'])]
class MultipleChoiceQuestion implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_job', 'ai_evaluation_post'])]
    #[OA\Property(property: 'id', description: 'Multiple Choice Question ID', type: 'string')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une question.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post', 'ai_evaluation_job'])]
    private ?string $question = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir une explication.', allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post', 'ai_evaluation_job'])]
    private ?string $explanation = null;

    #[ORM\OneToMany(mappedBy: 'multipleChoiceQuestion', targetEntity: Choice::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post', 'ai_evaluation_job'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $choices;

    #[ORM\ManyToOne(inversedBy: 'multipleChoiceQuestions', targetEntity: EnrichmentVersion::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?EnrichmentVersion $enrichmentVersion = null;

    #[ORM\OneToOne(mappedBy: 'multipleChoiceQuestion', targetEntity: AnswerPointer::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(groups: ['enrichment_versions', 'ai_enrichment_post'])]
    #[Assert\Valid]
    private ?AnswerPointer $answerPointer = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Json]
    #[Groups(groups: ['enrichment_versions', 'ai_evaluation_post'])]
    #[OA\Property(property: 'evaluation', description: "MCQ's evaluation", type: 'object', properties: [
        new OA\Property(
            property: 'criteria1',
            description: 'Returns whether the criteria is verified for the MCQ',
            type: 'boolean'
        ),
        new OA\Property(
            property: 'criteria2',
            description: 'Returns whether the criteria is verified for the MCQ',
            type: 'boolean'
        ),
        new OA\Property(
            property: 'criteria3',
            description: 'Returns whether the criteria is verified for the MCQ',
            type: 'boolean'
        ),
    ])]
    private ?string $evaluation = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?bool $thumbUp = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $userFeedback = null;

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

    public function setId(?Uuid $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getAnswerPointer(): ?AnswerPointer
    {
        return $this->answerPointer;
    }

    public function setAnswerPointer(?AnswerPointer $answerPointer): static
    {
        $this->answerPointer = $answerPointer;
        $answerPointer->setMultipleChoiceQuestion($this);

        return $this;
    }

    public function getEvaluation(): string
    {
        return $this->evaluation;
    }

    public function setEvaluation(?string $evaluation): self
    {
        $this->evaluation = $evaluation;

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

    public function getUserFeedback(): ?string
    {
        return $this->userFeedback;
    }

    public function setUserFeedback(?string $userFeedback): self
    {
        $this->userFeedback = $userFeedback;

        return $this;
    }
}
