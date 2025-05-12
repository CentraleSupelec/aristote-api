<?php

namespace App\MessageHandler;

use App\Constants;
use App\Entity\Enrichment;
use App\Exception\MediaDurationExceedsLimitException;
use App\Exception\TextLengthExceedsLimitException;
use App\Message\FileUploadFromUrlMessage;
use App\Repository\EnrichmentRepository;
use App\Service\FileUploadService;
use App\Utils\EnrichmentUtils;
use DateTime;
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
        private readonly LoggerInterface $logger,
        private readonly EnrichmentUtils $enrichmentUtils,
        private readonly int $maxUploadRetries,
    ) {
    }

    public function __invoke(FileUploadFromUrlMessage $fileUploadFromUrlMessage, int $retryCount): void
    {
        $enrichment = $this->enrichmentRepository->findOneBy(['id' => $fileUploadFromUrlMessage->getEnrichmentId()]);
        $enrichment->setStatus(Enrichment::STATUS_UPLOADING_MEDIA)->setUploadStartedAt(new DateTime());
        $this->entityManager->flush();

        $url = $fileUploadFromUrlMessage->getEnrichmentCreationUrlRequestPayload()->getUrl();

        try {
            $fileDetails = $this->getFileFromUrl($url);
            $temporaryFilePath = $fileDetails['filePath'];
            $fileName = $fileDetails['fileName'];
        } catch (Exception $exception) {
            $this->handleUploadFailure($enrichment, $exception, "Couldn't get file from URL", $retryCount);
            throw $exception;
        }

        try {
            $uploadedFile = new UploadedFile($temporaryFilePath, $fileName);
            if ('application/x-empty' === $uploadedFile->getMimeType()) {
                $errorMessage = "Uploaded file mimetype is 'application/x-empty'. Please report this issue.";

                if (file_exists($temporaryFilePath)) {
                    unlink($temporaryFilePath);
                }
                $this->handleUploadFailure($enrichment, new Exception($errorMessage), $errorMessage, $retryCount);
            } else {
                $enrichment = $this->fileUploadService->uploadFile($uploadedFile, $fileUploadFromUrlMessage->getApiClient(), $enrichment);
                $enrichment->setMediaUrl(null)->setUploadEndedAt(new DateTime());
                $this->entityManager->flush();
            }
            unlink($temporaryFilePath);
        } catch (Exception $exception) {
            if (file_exists($temporaryFilePath)) {
                unlink($temporaryFilePath);
            }

            if (!$exception instanceof MediaDurationExceedsLimitException && !$exception instanceof TextLengthExceedsLimitException) {
                $this->handleUploadFailure($enrichment, $exception, "Couldn't upload file to bucket", $retryCount);
            } else {
                $this->handleUploadFailure($enrichment, $exception, $exception->getMessage(), $retryCount);
            }
            throw $exception;
        }
    }

    private function getFileFromUrl(string $fileUrl): array
    {
        if (!is_dir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH)) {
            mkdir(Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, 0777, true);
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 600,
            ],
        ]);

        $stream = fopen($fileUrl, 'r', false, $context);

        if (!$stream) {
            throw new Exception('Failed to open the stream.');
        }

        $metaData = stream_get_meta_data($stream);
        $headers = $metaData['wrapper_data'];
        $fileContents = stream_get_contents($stream);
        fclose($stream);

        $fileName = null;
        foreach ($headers as $header) {
            if (false !== stripos((string) $header, 'Content-Disposition:')) {
                $parts = explode('filename=', (string) $header);
                if (count($parts) > 1) {
                    $fileName = trim($parts[1], '"');
                }
                break;
            }
        }

        if (!$fileName) {
            $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
        }
        $temporaryFileName = uniqid('file');

        $filePath = sprintf('%s/%s', Constants::TEMPORARY_STORAGE_FOR_WORKER_PATH, $temporaryFileName);
        file_put_contents($filePath, $fileContents);

        return [
            'filePath' => $filePath,
            'fileName' => $fileName,
        ];
    }

    private function handleUploadFailure(Enrichment $enrichment, Exception $exception, string $errorMessage, int $retryCount)
    {
        $this->logger->error($exception);
        $enrichment->setStatus(Enrichment::STATUS_FAILURE);
        $enrichment->setFailureCause($errorMessage);
        $this->entityManager->persist($enrichment);
        $this->entityManager->flush();

        if ($retryCount >= $this->maxUploadRetries) {
            $this->enrichmentUtils->sendNotification($enrichment);
        }
    }
}
