<?php

namespace App\Entity;

use App\Repository\SubtitleRepository;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: SubtitleRepository::class)]
#[Vich\Uploadable]
class Subtitle extends Media
{
    #[Vich\UploadableField(mapping: 'subtitles', fileNameProperty: 'fileName', size: 'size', mimeType: 'mimeType', originalName: 'originalFileName')]
    #[AppAssert\SubtitleFileConstraint]
    private ?File $subtitleFile = null;

    public function getSubtitleFile(): ?File
    {
        return $this->subtitleFile;
    }

    public function setSubtitleFile(File $subtitleFile = null): static
    {
        $this->subtitleFile = $subtitleFile;

        if ($subtitleFile instanceof File) {
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }
}
