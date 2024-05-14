<?php

namespace App\Tests\FixturesProvider;

use App\Entity\EnrichmentVersion;
use App\Entity\Transcript;
use Doctrine\ORM\EntityManagerInterface;

class EnrichmentVersionsFixturesProvider
{
    public function __construct(
    ) {
    }

    public static function getEnrichmentVersion(?EntityManagerInterface $entityManager): EnrichmentVersion
    {
        $enrichment = EnrichmentFixturesProvider::getEnrichment($entityManager);

        $enrichmentVersion = (new EnrichmentVersion())
            ->setInitialVersion(true)
            ->setTranscript((new Transcript())
                ->setLanguage('fr')
                ->setOriginalFilename('video.mp4')
                ->setText('Transcript')
                ->setSentences('[{"text": "Transcript", "start": 1, "end": 4}]')
            );

        $enrichment->addVersion($enrichmentVersion);

        if (null !== $entityManager) {
            $entityManager->persist($enrichment);
            $entityManager->flush();
        }

        return $enrichmentVersion;
    }
}
