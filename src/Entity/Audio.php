<?php

namespace App\Entity;

use App\Repository\AudioRepository;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AudioRepository::class)]
#[Vich\Uploadable]
class Audio extends Media
{
    #[Vich\UploadableField(mapping: 'audios', fileNameProperty: 'fileName', size: 'size', mimeType: 'mimeType', originalName: 'originalFileName')]
    #[AppAssert\AudioFileConstraint]
    private ?File $audioFile = null;

    public function getAudioFile(): ?File
    {
        return $this->audioFile;
    }

    public function setAudioFile(File $audioFile = null): static
    {
        $this->audioFile = $audioFile;

        if ($audioFile instanceof File) {
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }
}
