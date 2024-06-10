<?php

namespace App\Tests\Service;

use App\Entity\EnrichmentVersion;
use App\Repository\EnrichmentVersionRepository;
use App\Tests\FixturesProvider\EnrichmentVersionsFixturesProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EnrichmentRepositoryTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel([
            'debug' => false,
            'environment' => 'test',
        ]);

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testFindLatestAiVersionByEnrichmentId(): void
    {
        $enrichmentVersion1 = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichment = $enrichmentVersion1->getEnrichment();
        $enrichmentVersion1->setCreatedAt(new DateTime('-2 minute'));

        $enrichmentVersion2 = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichmentVersion2->setCreatedAt(new DateTime('-1 minute'));
        $enrichmentVersion2->setAiGenerated(false);

        $enrichmentVersion3 = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichmentVersion3->setCreatedAt(new DateTime());

        $this->entityManager->flush();

        /** @var EnrichmentVersionRepository $enrichmentVersionRepository */
        $enrichmentVersionRepository = $this->entityManager->getRepository(EnrichmentVersion::class);
        $latestAiVersion = $enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        $this->assertNotNull($latestAiVersion);
        $this->assertEquals($latestAiVersion->getId(), $enrichmentVersion3->getId());

        $enrichmentVersion3->setAiGenerated(false);
        $this->entityManager->flush();

        $latestAiVersion = $enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        $this->assertNotNull($latestAiVersion);
        $this->assertEquals($latestAiVersion->getId(), $enrichmentVersion1->getId());

        $enrichmentVersion2->setAiGenerated(true);
        $this->entityManager->flush();

        $latestAiVersion = $enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        $this->assertNotNull($latestAiVersion);
        $this->assertEquals($latestAiVersion->getId(), $enrichmentVersion2->getId());

        $enrichmentVersion1->setAiGenerated(false);
        $enrichmentVersion2->setAiGenerated(false);
        $this->entityManager->flush();

        $latestAiVersion = $enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        $this->assertNull($latestAiVersion);
    }
}
