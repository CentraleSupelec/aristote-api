<?php

namespace App\MessageHandler;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\Video;
use App\Message\VideoUploadFromUrlMessage;
use App\Repository\EnrichmentRepository;
use App\Service\VideoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VideoUploadFromUrlMessageHandler
{
    public function __construct(
        private readonly VideoUploadService $videoUploadService,
        private readonly EnrichmentRepository $enrichmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(VideoUploadFromUrlMessage $videoUploadFromUrlMessage): void
    {
        $enrichment = $this->enrichmentRepository->findOneBy(['id' => $videoUploadFromUrlMessage->getEnrichmentId()]);
        $enrichment->setStatus(Enrichment::STATUS_UPLOADING);
        $this->entityManager->persist($enrichment);
        $this->entityManager->flush();

        $videoUrl = $videoUploadFromUrlMessage->getEnrichmentCreationVideoUrlRequestPayload()->getVideoUrl();

        try {
            $videoName = $this->getVideoFromUrl($videoUrl);
        } catch (Exception $exception) {
            $this->handleUploadFailure($enrichment, $exception, "Couldn't get video from URL");
            throw $exception;
        }

        $temporaryVideoPath = sprintf('%s/%s', Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, $videoName);

        try {
            $uploadedFile = new UploadedFile($temporaryVideoPath, $videoUrl);
            $videoOrErrorsArray = $this->videoUploadService->uploadVideo($uploadedFile, $videoUploadFromUrlMessage->getApiClient(), $enrichment);

            unlink($temporaryVideoPath);
            if (!$videoOrErrorsArray instanceof Video) {
                $errorMessage = implode(',', $videoOrErrorsArray);
                $this->handleUploadFailure($enrichment, new Exception($errorMessage), $errorMessage);
            }
        } catch (Exception $exception) {
            if (file_exists($temporaryVideoPath)) {
                unlink($temporaryVideoPath);
            }
            $this->handleUploadFailure($enrichment, $exception, "Couldn't upload video to bucket");
            throw $exception;
        }
    }

    private function getVideoFromUrl(string $videoUrl): string
    {
        $fileName = uniqid('video_').'.mp4';

        if (!is_dir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH)) {
            mkdir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, 0777, true);
        }

        $videoContents = file_get_contents($videoUrl);
        file_put_contents(sprintf('%s/%s', Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, $fileName), $videoContents);

        return $fileName;
    }

    private function handleUploadFailure(Enrichment $enrichment, Exception $exception, string $errorMessage)
    {
        $this->logger->error($exception);
        $enrichment->setStatus(Enrichment::STATUS_FAILURE);
        $enrichment->setFailureCause($errorMessage);
        $this->entityManager->persist($enrichment);
        $this->entityManager->flush();
    }
}
