<?php

namespace App\Utils;

use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Exception\EnrichmentWebhookPayloadValidationException;
use App\Model\EnrichmentWebhookPayload;
use App\Repository\EnrichmentVersionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EnrichmentUtils
{
    public function __construct(
        private readonly EnrichmentVersionRepository $enrichmentVersionRepository,
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function calculateEvaluationMark(Enrichment $enrichment): ?float
    {
        $latestAiVersion = $this->enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        if (!$latestAiVersion instanceof EnrichmentVersion) {
            return null;
        }

        $totalThumbs = 0;
        $totalThubmsUp = 0;
        $mcqs = $latestAiVersion->getMultipleChoiceQuestions();
        foreach ($mcqs as $mcq) {
            /** @var MultipleChoiceQuestion $mcq */
            if (null !== $mcq->getThumbUp()) {
                ++$totalThumbs;
                $totalThubmsUp += $mcq->getThumbUp() ? 1 : 0;
            }
        }

        return 0 !== $totalThumbs ? $totalThubmsUp / $totalThumbs : null;
    }

    private function subtitlesLine(string $format, string $start, string $end, string $text, int $index): string
    {
        if ('srt' === $format) {
            return sprintf(
                "%d\n%s --> %s\n%s\n\n",
                $index,
                $start,
                $end,
                $text
            );
        } else {
            return sprintf(
                "%s --> %s\n%s\n\n",
                $start,
                $end,
                $text
            );
        }
    }

    private function convertTime(float $seconds, string $millisecondsSeparator): string
    {
        $hours = str_pad(floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad(floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $secs = str_pad(floor($seconds % 60), 2, '0', STR_PAD_LEFT);
        $millis = str_pad(floor(($seconds - floor($seconds)) * 1000), 3, '0', STR_PAD_LEFT);

        return sprintf('%s:%s:%s%s%s', $hours, $minutes, $secs, $millisecondsSeparator, $millis);
    }

    public function transcriptToSubtitles(Transcript $transcript, bool $pickTranslated = false, string $format = 'srt'): string
    {
        if ('vtt' === $format) {
            $result = "WEBVTT\n\n";
            $timeSeprator = '.';
        } elseif ('srt' === $format) {
            $result = '';
            $timeSeprator = ',';
        } else {
            return null;
        }

        $sentences = json_decode(
            $pickTranslated ? $transcript->getTranslatedSentences() : $transcript->getSentences(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $lineIndex = 0;

        foreach ($sentences as $sentence) {
            if (array_key_exists('words', $sentence)) {
                $words = $sentence['words'];
                $line = '';
                $ind = 0;

                foreach ($words as $wordIndex => $word) {
                    $wordText = $word['text'];

                    if (0 === $ind) {
                        $start = $this->convertTime($word['start'], $timeSeprator);
                        $line .= ltrim((string) $wordText);
                    } else {
                        $line .= $wordText;
                    }

                    ++$ind;
                    if (
                        str_ends_with((string) $wordText, '.')
                        || str_ends_with((string) $wordText, '.')
                        || $wordIndex === (is_countable($words) ? count($words) : 0) - 1
                        || ($ind > 12 && !str_starts_with((string) $words[$wordIndex + 1]['text'], "'"))
                    ) {
                        $result .= $this->subtitlesLine($format, $start, $this->convertTime($word['end'], $timeSeprator), $line, ++$lineIndex);
                        $ind = 0;
                        $line = '';
                    }
                }
            } else {
                $start = $this->convertTime($sentence['start'], $timeSeprator);
                $end = $this->convertTime($sentence['end'], $timeSeprator);
                $result .= $this->subtitlesLine($format, $start, $end, $sentence['text'], ++$lineIndex);
            }
        }

        return $result;
    }

    public function sendNotification(Enrichment $enrichment, ?string $completedStep = null): void
    {
        $latestAiVersion = $this->enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        $enrichmentWebhookPayload = (new EnrichmentWebhookPayload())
            ->setId($enrichment->getId())
            ->setStatus($enrichment->getStatus())
            ->setFailureCause($enrichment->getFailureCause())
            ->setInitialVersionId($latestAiVersion?->getId())
            ->setCompletedStep($completedStep)
        ;

        $errors = $this->validator->validate($enrichmentWebhookPayload);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            $this->logger->error($errorsArray);
            throw new EnrichmentWebhookPayloadValidationException();
        }

        try {
            $serialized = $this->serializer->serialize($enrichmentWebhookPayload, 'json');
            $response = $this->httpClient->request('POST', $enrichment->getNotificationWebhookUrl(), [
                'body' => $serialized,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
            $enrichment->setNotificationStatus($response->getStatusCode());
            $latestAiVersion?->setNotificationStatus($response->getStatusCode());
            if (200 === $response->getStatusCode()) {
                $enrichment->setNotifiedAt(new DateTime());
                $latestAiVersion?->setNotifiedAt(new DateTime());
            }
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $enrichment->setNotificationStatus($e->getCode());
            $latestAiVersion?->setNotificationStatus($e->getCode());
            $this->entityManager->flush();
        }
    }
}
