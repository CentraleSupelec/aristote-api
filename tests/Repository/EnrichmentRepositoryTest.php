<?php

namespace App\Tests\Service;

use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
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

    public function testFindOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes(): void
    {
        $enrichment1 = EnrichmentFixturesProvider::getEnrichment($this->entityManager);
        $enrichment2 = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $enrichment1->getCreatedBy());
        $enrichment3 = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $enrichment1->getCreatedBy());
        $enrichment4 = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $enrichment1->getCreatedBy());
        $enrichment5 = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $enrichment1->getCreatedBy());

        $enrichment1->setLatestEnrichmentRequestedAt(new DateTime('-30 minute'))->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);
        $enrichment2->setLatestEnrichmentRequestedAt(new DateTime('-25 minute'))->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);
        $enrichment3->setLatestEnrichmentRequestedAt(new DateTime('-20 minute'))->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);
        $enrichment4->setLatestEnrichmentRequestedAt(new DateTime('-15 minute'))->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);
        $enrichment5->setLatestEnrichmentRequestedAt(new DateTime('-10 minute'))->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        /** @var EnrichmentRepository $enrichmentRepository */
        $enrichmentRepository = $this->entityManager->getRepository(Enrichment::class);
        $foundEnrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes();

        $this->assertNotNull($foundEnrichment);
        $this->assertEquals($foundEnrichment->getId(), $enrichment1->getId());

        $enrichment3->setLatestEnrichmentRequestedAt(new DateTime('-40 minute'));
        $this->entityManager->flush();

        $foundEnrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes();

        $this->assertNotNull($foundEnrichment);
        $this->assertEquals($foundEnrichment->getId(), $enrichment3->getId());

        $enrichment5->setPriority(5);
        $this->entityManager->flush();

        $foundEnrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes();

        $this->assertNotNull($foundEnrichment);
        $this->assertEquals($foundEnrichment->getId(), $enrichment5->getId());

        $enrichment4->setPriority(4);
        $this->entityManager->flush();

        $foundEnrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes();

        $this->assertNotNull($foundEnrichment);
        $this->assertEquals($foundEnrichment->getId(), $enrichment5->getId());

        $enrichment4->setPriority(5);
        $this->entityManager->flush();

        $foundEnrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes();

        $this->assertNotNull($foundEnrichment);
        $this->assertEquals($foundEnrichment->getId(), $enrichment4->getId());
    }
}
