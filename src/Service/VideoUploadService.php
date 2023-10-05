<?php

namespace App\Service;

use App\Entity\Enrichment;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VideoUploadService
{
    public function __construct(
        private readonly string $baseDirectory,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly ApiClientManager $apiClientManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function uploadVideo(UploadedFile $uploadedFile): Video|array
    {
        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $this->apiClientManager->getClientEntity($clientId);
        $directory = $this->baseDirectory.$clientEntity->getIdentifier();

        $media = (new Video())
            ->setVideoFile($uploadedFile)
            ->setFileDirectory($directory)
        ;

        $enrichment = (new Enrichment())
            ->setStatus(Enrichment::STATUS_PENDING)
            ->setMedia($media)
            ->setCreatedBy($clientEntity)
        ;

        $constraintViolationList = $this->validator->validate($enrichment);
        if (count($constraintViolationList) > 0) {
            return array_map(fn ($error) => $error->getMessage(), iterator_to_array($constraintViolationList));
        }

        $this->entityManager->persist($media);
        $this->entityManager->persist($enrichment);
        $this->entityManager->flush();

        return $media;
    }
}
