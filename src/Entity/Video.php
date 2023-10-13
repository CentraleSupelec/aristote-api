<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use App\Validator\Constraints as AppAssert;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[Vich\Uploadable]
class Video extends Media
{
    #[Vich\UploadableField(mapping: 'videos', fileNameProperty: 'fileName', size: 'size', mimeType: 'mimeType', originalName: 'originalFileName')]
    #[AppAssert\VideoFileConstraint]
    private ?File $videoFile = null;

    public function getVideoFile(): ?File
    {
        return $this->videoFile;
    }

    public function setVideoFile(File $videoFile = null): static
    {
        $this->videoFile = $videoFile;

        if ($videoFile instanceof File) {
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }
}
