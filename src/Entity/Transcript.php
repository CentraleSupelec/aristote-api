<?php

namespace App\Entity;

use App\Repository\TranscriptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
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
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $originalFilename = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $language = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(groups: ['enrichment_versions'])]
    private ?string $text = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Json]
    #[Groups(groups: ['enrichment_versions'])]
    #[OA\Property(property: 'sentences', description: "Transcipt's sentences", type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(
            property: 'is_transient',
            description: 'Returns the current page number',
            type: 'boolean'
        ),
        new OA\Property(
            property: 'no_speech_prob',
            description: 'Returns the current page number',
            type: 'integer',
            format: 'int64'
        ),
        new OA\Property(
            property: 'start',
            description: 'Returns the current page number',
            type: 'integer',
            format: 'int64'
        ),
        new OA\Property(
            property: 'end',
            description: 'Returns the current page number',
            type: 'integer',
            format: 'int64'
        ),
        new OA\Property(
            property: 'text',
            description: 'Returns the current page number',
            type: 'string'
        ),
    ]))]
    private ?string $sentences;

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

    public function getSentences(): string
    {
        return $this->sentences;
    }

    public function setSentences(?string $sentences): self
    {
        $this->sentences = $sentences;

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
