<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\InheritanceType('JOINED')]
class Media
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $originalFileName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(groups: ['enrichments'])]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?int $size = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $fileDirectory = null;

    #[ORM\OneToOne(inversedBy: 'media', targetEntity: Enrichment::class)]
    private ?Enrichment $enrichment = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(?string $originalFileName): static
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileDirectory(): ?string
    {
        return $this->fileDirectory;
    }

    public function setFileDirectory(?string $fileDirectory): static
    {
        $this->fileDirectory = $fileDirectory;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getEnrichment(): ?Enrichment
    {
        return $this->enrichment;
    }

    public function setEnrichment(?Enrichment $enrichment): static
    {
        $this->enrichment = $enrichment;

        return $this;
    }
}
