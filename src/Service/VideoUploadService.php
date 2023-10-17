<?php

namespace App\Service;

use App\Entity\ApiClient;
use App\Entity\Enrichment;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VideoUploadService
{
    public function __construct(
        private readonly string $baseDirectory,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function uploadVideo(UploadedFile $uploadedFile, ApiClient $apiClient, Enrichment $enrichment = null): Video|array
    {
        $directory = $this->baseDirectory.$apiClient->getIdentifier();

        $video = (new Video())
            ->setVideoFile($uploadedFile)
            ->setFileDirectory($directory)
        ;

        if (!$enrichment instanceof Enrichment) {
            $enrichment = (new Enrichment())
                ->setStatus(Enrichment::STATUS_PENDING)
                ->setMedia($video)
                ->setCreatedBy($apiClient)
            ;

            $constraintViolationList = $this->validator->validate($enrichment);
            if (count($constraintViolationList) > 0) {
                return array_map(fn ($error) => $error->getMessage(), iterator_to_array($constraintViolationList));
            }
            $this->entityManager->persist($enrichment);
        } else {
            $enrichment
                ->setMedia($video)
                ->setStatus(Enrichment::STATUS_PENDING);
        }
        $this->entityManager->flush();

        return $video;
    }
}
