<?php

namespace App\Service;

use App\Constants;
use App\Entity\ApiClient;
use App\Entity\Enrichment;
use App\Entity\Video;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    public function __construct(
        private readonly string $baseDirectory,
        private readonly string $bucketName,
        private readonly int $linkExpirationInMinutes,
        private readonly S3Client $s3Client
    ) {
    }

    public function uploadFile(UploadedFile $uploadedFile, ApiClient $apiClient, Enrichment $enrichment): Enrichment
    {
        $directory = $this->baseDirectory.$apiClient->getIdentifier();

        $video = (new Video())
            ->setVideoFile($uploadedFile)
            ->setFileDirectory($directory)
        ;

        $enrichment
            ->setMedia($video)
            ->setStatus(Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION)
        ;

        return $enrichment;
    }

    public function generatePublicLink(string $filePath): string
    {
        $expirationDate = time() + $this->linkExpirationInMinutes * 60;
        $command = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucketName,
            'Key' => Constants::MEDIAS_PREFIX.'/'.$filePath,
            'Expires' => $expirationDate,
        ]);

        return $this->s3Client->createPresignedRequest($command, $expirationDate)->getUri();
    }
}
