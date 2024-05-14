<?php

namespace App\MessageHandler;

use App\Constants;
use App\Entity\Enrichment;
use App\Message\FileUploadFromUrlMessage;
use App\Repository\EnrichmentRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FileUploadFromUrlMessageHandler
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly EnrichmentRepository $enrichmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(FileUploadFromUrlMessage $fileUploadFromUrlMessage): void
    {
        $enrichment = $this->enrichmentRepository->findOneBy(['id' => $fileUploadFromUrlMessage->getEnrichmentId()]);
        $enrichment->setStatus(Enrichment::STATUS_UPLOADING_MEDIA);
        $this->entityManager->flush();

        $url = $fileUploadFromUrlMessage->getEnrichmentCreationUrlRequestPayload()->getUrl();

        try {
            $temporaryFilePath = $this->getFileFromUrl($url);
        } catch (Exception $exception) {
            $this->handleUploadFailure($enrichment, $exception, "Couldn't get file from URL");
            throw $exception;
        }

        try {
            $uploadedFile = new UploadedFile($temporaryFilePath, $url);
            if ('application/x-empty' === $uploadedFile->getMimeType()) {
                $errorMessage = "Uploaded file mimetype is 'application/x-empty'. Please report this issue.";

                if (file_exists($temporaryFilePath)) {
                    unlink($temporaryFilePath);
                }
                $this->handleUploadFailure($enrichment, new Exception($errorMessage), $errorMessage);
            } else {
                $enrichment = $this->fileUploadService->uploadFile($uploadedFile, $fileUploadFromUrlMessage->getApiClient(), $enrichment);
                $this->entityManager->flush();
            }
            unlink($temporaryFilePath);
        } catch (Exception $exception) {
            if (file_exists($temporaryFilePath)) {
                unlink($temporaryFilePath);
            }
            $this->handleUploadFailure($enrichment, $exception, "Couldn't upload file to bucket");
            throw $exception;
        }
    }

    private function getFileFromUrl(string $fileUrl): string
    {
        $fileName = uniqid('file');

        if (!is_dir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH)) {
            mkdir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, 0777, true);
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $fileContents = file_get_contents($fileUrl, false, $context);
        $filePath = sprintf('%s/%s', Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, $fileName);
        file_put_contents($filePath, $fileContents);

        return $filePath;
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
