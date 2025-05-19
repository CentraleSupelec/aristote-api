<?php

namespace App\Service;

use App\Constants;
use App\Entity\ApiClient;
use App\Entity\Audio;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\Subtitle;
use App\Entity\Transcript;
use App\Entity\Video;
use App\Exception\MediaDurationExceedsLimitException;
use App\Exception\TextLengthExceedsLimitException;
use App\Exception\UploadFileUnsupportedTypeException;
use App\Repository\ParameterRepository;
use App\Utils\MimeTypeUtils;
use Aws\S3\S3Client;
use Captioning\Cue;
use Captioning\Format\SubripFile;
use Captioning\Format\WebvttFile;
use Exception;
use FFMpeg\FFMpeg;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    private readonly FFMpeg $fFMpeg;

    public function __construct(
        private readonly string $baseDirectory,
        private readonly string $bucketName,
        private readonly int $linkExpirationInMinutes,
        private readonly S3Client $s3Client,
        private readonly MimeTypeUtils $mimeTypeUtils,
        private readonly LoggerInterface $logger,
        private readonly ParameterRepository $parameterRepository,
    ) {
        $this->fFMpeg = FFMpeg::create();
    }

    public function uploadFile(UploadedFile $uploadedFile, ApiClient $apiClient, Enrichment $enrichment): Enrichment
    {
        $directory = $this->baseDirectory.'/%s/'.$apiClient->getIdentifier();
        $testing = 'test' === $_ENV['APP_ENV'];
        $mimeType = $testing ? $uploadedFile->getClientMimeType() : $uploadedFile->getMimeType();

        if ($this->mimeTypeUtils->isVideo($mimeType)) {
            $directory = sprintf($directory, 'videos');
            $duration = $testing ? 0 : $this->fFMpeg->open($uploadedFile->getPathname())->getFormat()->get('duration');

            $maxMediaDurationInSeconds = $this->parameterRepository->findIntegerParameterByName(Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS);

            if ($maxMediaDurationInSeconds > 0 && $duration > $maxMediaDurationInSeconds) {
                $failureCause = sprintf('Video duration (%s s) exceeds maximum accepted media duration (%s s)', ceil($duration), $maxMediaDurationInSeconds);
                $enrichment
                    ->setStatus(Enrichment::STATUS_FAILURE)
                    ->setFailureCause($failureCause)
                ;

                throw new MediaDurationExceedsLimitException($failureCause);
            }

            $media = (new Video())
                ->setVideoFile($uploadedFile)
                ->setFileDirectory($directory)
                ->setDuration((int) $duration)
            ;
            $enrichment->setMediaDurationInSeconds($duration);
            $targetStatus = Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION;
        } elseif ($this->mimeTypeUtils->isAudio($mimeType)) {
            $directory = sprintf($directory, 'audios');
            $duration = $testing ? 0 : $this->fFMpeg->open($uploadedFile->getPathname())->getFormat()->get('duration');

            $maxMediaDurationInSeconds = $this->parameterRepository->findIntegerParameterByName(Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS);

            if ($maxMediaDurationInSeconds > 0 && $duration > $maxMediaDurationInSeconds) {
                $failureCause = sprintf('Audio duration (%s s) exceeds maximum accepted media duration (%s s)', ceil($duration), $maxMediaDurationInSeconds);
                $enrichment
                    ->setStatus(Enrichment::STATUS_FAILURE)
                    ->setFailureCause($failureCause)
                ;
                throw new MediaDurationExceedsLimitException($failureCause);
            }

            $media = (new Audio())
                ->setAudioFile($uploadedFile)
                ->setFileDirectory($directory)
                ->setDuration((int) $duration)
            ;
            $enrichment->setMediaDurationInSeconds($duration);
            $targetStatus = Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION;
        } elseif ($this->mimeTypeUtils->isSubtitleFile($mimeType)) {
            $directory = sprintf($directory, 'subtitles');
            $media = (new Subtitle())
                ->setSubtitleFile($uploadedFile)
                ->setFileDirectory($directory)
            ;

            $targetStatus = Enrichment::STATUS_SUCCESS;
            if ($enrichment->getGenerateMetadata() || $enrichment->getGenerateQuiz() || $enrichment->getGenerateNotes()) {
                $targetStatus = Enrichment::STATUS_WAITING_AI_ENRICHMENT;
            } elseif ($enrichment->getTranslateTo()) {
                $targetStatus = Enrichment::STATUS_WAITING_TRANSLATION;
            } elseif ($enrichment->getAiEvaluation()) {
                $targetStatus = Enrichment::STATUS_WAITING_AI_EVALUATION;
            }

            $parsingVttFailed = false;
            $subtitles = null;
            try {
                $content = $uploadedFile->getContent();
                $subtitles = new WebvttFile();
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                if (substr($content, 0, 3) === $bom) {
                    $content = substr($content, 3);
                }
                $subtitles->loadFromString(trim($content));
            } catch (Exception $exception) {
                $parsingVttFailed = true;
            }

            if ($parsingVttFailed) {
                try {
                    $subtitles = new SubripFile(_filename: $uploadedFile->getPathname(), _requireStrictFileFormat: false);
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

            $maxTextLength = $this->parameterRepository->findIntegerParameterByName(Constants::PARAMETER_MAX_TEXT_LENGTH);

            if ($maxTextLength > 0 && strlen($text) > $maxTextLength) {
                $failureCause = sprintf('Text length (%s) exceeds maximum accepted lenght (%s)', strlen($text), $maxTextLength);
                $enrichment
                    ->setStatus(Enrichment::STATUS_FAILURE)
                    ->setFailureCause($failureCause)
                ;
                throw new TextLengthExceedsLimitException($failureCause);
            }

            $transcript = (new Transcript())
                ->setOriginalFilename($uploadedFile->getClientOriginalName())
                ->setSentences(json_encode($sentences, JSON_THROW_ON_ERROR))
                ->setText($text)
                ->setLanguage($enrichment->getLanguage() ?? Enrichment::LANGUAGE_FR)
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
                ->setLanguage($enrichment->getLanguage())
                ->setTranslateTo($enrichment->getTranslateTo())
                ->setGenerateMetadata($enrichment->getGenerateMetadata())
                ->setGenerateQuiz($enrichment->getGenerateQuiz())
                ->setGenerateNotes($enrichment->getGenerateNotes())
            ;
            $enrichment->setMediaTextLength(strlen($text));
            $enrichment->addVersion($enrichmentVersion)->setAiGenerationCount(1);
            $enrichment->setInitialEnrichmentVersion($enrichmentVersion);
            $enrichment->setLastEnrichmentVersion($enrichmentVersion);
        } else {
            throw new UploadFileUnsupportedTypeException('File type not supported. Supported types are videos, audio files and subtitles');
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
