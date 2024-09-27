<?php

namespace App\Entity;

use App\Model\Sentence;
use App\Repository\TranscriptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TranscriptRepository::class)]
class Transcript
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job'])]
    private ?string $originalFilename = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'ai_evaluation_job'])]
    private ?string $language = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'ai_evaluation_job', 'translation_job', 'translation_post'])]
    private ?string $text = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $translatedText = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Json]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'translation_job', 'translation_post'])]
    #[OA\Property(property: 'sentences', description: "Transcipt's sentences", type: 'array', items: new OA\Items(
        ref: new Model(type: Sentence::class)
    ))]
    private ?string $sentences;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Json]
    #[Groups(groups: ['enrichment_versions'])]
    #[OA\Property(property: 'translatedSentences', description: "Transcipt's translated sentences", type: 'array', items: new OA\Items(
        ref: new Model(type: Sentence::class)
    ))]
    private ?string $translatedSentences = null;

    #[ORM\OneToOne(inversedBy: 'transcript', targetEntity: EnrichmentVersion::class)]
    private ?EnrichmentVersion $enrichmentVersion = null;

    public function __construct()
    {
        $this->sentences = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getTranslatedText(): ?string
    {
        return $this->translatedText;
    }

    public function setTranslatedText(?string $translatedText): self
    {
        $this->translatedText = $translatedText;

        return $this;
    }

    public function getSentences(): ?string
    {
        return $this->sentences;
    }

    public function setSentences(?string $sentences): self
    {
        $this->sentences = $sentences;

        return $this;
    }

    public function getTranslatedSentences(): ?string
    {
        return $this->translatedSentences;
    }

    public function setTranslatedSentences(?string $translatedSentences): self
    {
        $this->translatedSentences = $translatedSentences;

        return $this;
    }

    public function getEnrichmentVersion(): ?EnrichmentVersion
    {
        return $this->enrichmentVersion;
    }

    public function setEnrichmentVersion(?EnrichmentVersion $enrichmentVersion): self
    {
        $this->enrichmentVersion = $enrichmentVersion;

        return $this;
    }
}
