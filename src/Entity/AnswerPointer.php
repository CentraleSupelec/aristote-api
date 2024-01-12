<?php

namespace App\Entity;

use App\Repository\AnswerPointerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnswerPointerRepository::class)]
class AnswerPointer
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'ai_enrichment_post', 'enrichment_version_creation'])]
    private ?string $startAnswerPointer = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'ai_enrichment_post', 'enrichment_version_creation'])]
    private ?string $stopAnswerPointer = null;

    #[ORM\OneToOne(inversedBy: 'answerPointer', targetEntity: MultipleChoiceQuestion::class)]
    private ?MultipleChoiceQuestion $multipleChoiceQuestion = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getStartAnswerPointer(): ?string
    {
        return $this->startAnswerPointer;
    }

    public function setStartAnswerPointer(?string $startAnswerPointer): self
    {
        $this->startAnswerPointer = $startAnswerPointer;

        return $this;
    }

    public function getStopAnswerPointer(): ?string
    {
        return $this->stopAnswerPointer;
    }

    public function setStopAnswerPointer(?string $stopAnswerPointer): self
    {
        $this->stopAnswerPointer = $stopAnswerPointer;

        return $this;
    }

    public function getMultipleChoiceQuestion(): ?MultipleChoiceQuestion
    {
        return $this->multipleChoiceQuestion;
    }

    public function setMultipleChoiceQuestion(?MultipleChoiceQuestion $multipleChoiceQuestion): self
    {
        $this->multipleChoiceQuestion = $multipleChoiceQuestion;

        return $this;
    }
}
