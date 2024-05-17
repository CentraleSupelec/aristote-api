<?php

namespace App\Service;

use App\Constants;
use App\Entity\ApiClient;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\Subtitle;
use App\Entity\Transcript;
use App\Entity\Video;
use App\Exception\UploadFileUnsupportedTypeException;
use App\Utils\MimeTypeUtils;
use Aws\S3\S3Client;
use Captioning\Cue;
use Captioning\Format\SubripFile;
use Captioning\Format\WebvttFile;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    public function __construct(
        private readonly string $baseDirectory,
        private readonly string $bucketName,
        private readonly int $linkExpirationInMinutes,
        private readonly S3Client $s3Client,
        private readonly MimeTypeUtils $mimeTypeUtils,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function uploadFile(UploadedFile $uploadedFile, ApiClient $apiClient, Enrichment $enrichment): Enrichment
    {
        $directory = $this->baseDirectory.'/%s/'.$apiClient->getIdentifier();
        $mimeType = $uploadedFile->getMimeType();

        if ($this->mimeTypeUtils->isVideo($mimeType)) {
            $directory = sprintf($directory, 'videos');
            $media = (new Video())
                ->setVideoFile($uploadedFile)
                ->setFileDirectory($directory)
            ;
            $targetStatus = Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION;
        } elseif ($this->mimeTypeUtils->isPlainText($mimeType)) {
            $directory = sprintf($directory, 'subtitles');
            $media = (new Subtitle())
                ->setSubtitleFile($uploadedFile)
                ->setFileDirectory($directory)
            ;
            $targetStatus = Enrichment::STATUS_WAITING_AI_ENRICHMENT;
            $parsingVttFailed = false;

            try {
                $subtitles = new WebvttFile($uploadedFile->getPathname());
            } catch (Exception) {
                $parsingVttFailed = true;
            }

            if ($parsingVttFailed) {
                try {
                    $subtitles = new SubripFile($uploadedFile->getPathname());
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    throw new UploadFileUnsupportedTypeException("Couldn't parse subtitles file");
                }
            }

            $sentences = [];
            $cues = $subtitles->getCues();
            $text = '';
            foreach ($cues as $key => $cue) {
                /** @var Cue $cue */
                $text = $text.' '.$cue->getText();
                $sentence = [
                    'start' => round($cue->getStartMS() / 1000, 2),
                    'end' => round($cue->getStopMS() / 1000, 2),
                    'text' => $cue->getText(),
                ];
                $sentences[$key] = $sentence;
            }
            $transcript = (new Transcript())
                ->setOriginalFilename($uploadedFile->getClientOriginalName())
                ->setSentences(json_encode($sentences, JSON_THROW_ON_ERROR))
                ->setText($text)
                ->setLanguage('fr')
            ;

            $enrichmentVersion = (new EnrichmentVersion())
                ->setInitialVersion(true)
                ->setAiGenerated(true)
                ->setTranscript($transcript)
                ->setNotificationWebhookUrl($enrichment->getNotificationWebhookUrl())
                ->setDisciplines($enrichment->getDisciplines())
                ->setMediaTypes($enrichment->getMediaTypes())
                ->setAiEvaluation($enrichment->getAiEvaluation())
                ->setEndUserIdentifier($enrichment->getEndUserIdentifier())
                ->setAiModel($enrichment->getAiModel())
                ->setInfrastructure($enrichment->getInfrastructure())
            ;

            $enrichment->addVersion($enrichmentVersion);
        } else {
            throw new UploadFileUnsupportedTypeException('File type not supported. Supported types are videos and subtitles');
        }

        $enrichment
            ->setMedia($media)
            ->setStatus($targetStatus)
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
