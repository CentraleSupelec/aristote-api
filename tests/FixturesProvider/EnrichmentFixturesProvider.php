<?php

namespace App\Tests\FixturesProvider;

use App\Entity\ApiClient;
use App\Entity\Enrichment;
use App\Entity\Video;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class EnrichmentFixturesProvider
{
    public function __construct(
    ) {
    }

    public static function getEnrichment(?EntityManagerInterface $entityManager, ?ApiClient $apiClient = null): Enrichment
    {
        if (!$apiClient instanceof ApiClient) {
            $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($entityManager);
        }

        $enrichment = (new Enrichment())
            ->setMedia(
                (new Video())
                    ->setMimeType('video/mp4')
                    ->setFileName('video_file.mp4')
                    ->setOriginalFileName('video_file.mp4')
                    ->setSize(1000)
                    ->setFileDirectory('dev')
            )
            ->setDisciplines(['Maths', 'Physics', 'Chemestry'])
            ->setMediaTypes(['Conference', 'Course', 'Webinar'])
            ->setNotificationWebhookUrl('http://localhost.fr/api/webhook')
            ->setStatus(Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION)
            ->setCreatedBy($apiClient)
            ->setLatestEnrichmentRequestedAt(new DateTime())
        ;

        if (null !== $entityManager) {
            $entityManager->persist($enrichment);
            $entityManager->flush();
        }

        return $enrichment;
    }
}
