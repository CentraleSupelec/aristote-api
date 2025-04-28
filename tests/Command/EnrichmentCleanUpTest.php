<?php

namespace App\Tests\centralesupelec\Application\Command;

use App\Entity\Enrichment;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class EnrichmentCleanUpTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected KernelInterface $client;

    protected function setUp(): void
    {
        $this->client = self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->enrichmentRepository = $this->entityManager->getRepository(Enrichment::class);
    }

    public function testCleanUpEnrichmentsCommand()
    {
        $application = new Application($this->client);
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $enrichmentNotToClean = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $enrichmentStuckAtTranscriptionNotReachedTimeout = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtTranscriptionNotReachedTimeout
            ->setTranscriptionRetries(10)
            ->setTranscribingStartedAt(new DateTime('-10 minute'))
        ;

        $enrichmentStuckAtTranscription = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtTranscription
            ->setTranscriptionRetries(10)
            ->setTranscribingStartedAt(new DateTime('-180 minute'))
        ;

        $enrichmentStuckAtEnrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtEnrichment
            ->setEnrichmentRetries(10)
            ->setAiEnrichmentStartedAt(new DateTime('-180 minute'))
            ->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)
        ;

        $enrichmentStuckAtTranslation = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtTranslation
            ->setTranslationRetries(10)
            ->setTranslationStartedAt(new DateTime('-180 minute'))
            ->setStatus(Enrichment::STATUS_WAITING_TRANSLATION)
        ;

        $enrichmentStuckAtEvaluation = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtEvaluation
            ->setEvaluationRetries(10)
            ->setAiEvaluationStartedAt(new DateTime('-180 minute'))
            ->setStatus(Enrichment::STATUS_WAITING_AI_EVALUATION)
        ;

        $enrichmentStuckAtUploadingNotReachedTimeout = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtUploadingNotReachedTimeout->setStatus(Enrichment::STATUS_UPLOADING_MEDIA)->setUploadStartedAt(new DateTime('-10 minute'));

        $enrichmentStuckAtUploading = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentStuckAtUploading->setStatus(Enrichment::STATUS_UPLOADING_MEDIA)->setUploadStartedAt(new DateTime('-180 minute'));

        $this->entityManager->flush();

        $command = $application->find('app:enrichment-clean');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->entityManager->refresh($enrichmentStuckAtTranscription);
        $this->entityManager->refresh($enrichmentStuckAtEnrichment);
        $this->entityManager->refresh($enrichmentStuckAtTranslation);
        $this->entityManager->refresh($enrichmentStuckAtEvaluation);

        $this->assertEquals('Max transcription retries reached (10)', $enrichmentStuckAtTranscription->getFailureCause());
        $this->assertEquals('Max enrichment retries reached (10)', $enrichmentStuckAtEnrichment->getFailureCause());
        $this->assertEquals('Max translation retries reached (10)', $enrichmentStuckAtTranslation->getFailureCause());
        $this->assertEquals('Max evaluation retries reached (10)', $enrichmentStuckAtEvaluation->getFailureCause());
        $this->assertEquals('Uploading took too long', $enrichmentStuckAtUploading->getFailureCause());

        $this->assertEquals(Enrichment::STATUS_FAILURE, $enrichmentStuckAtTranscription->getStatus());
        $this->assertEquals(Enrichment::STATUS_FAILURE, $enrichmentStuckAtEnrichment->getStatus());
        $this->assertEquals(Enrichment::STATUS_FAILURE, $enrichmentStuckAtTranslation->getStatus());
        $this->assertEquals(Enrichment::STATUS_FAILURE, $enrichmentStuckAtEvaluation->getStatus());
        $this->assertEquals(Enrichment::STATUS_FAILURE, $enrichmentStuckAtUploading->getStatus());

        $this->assertStringContainsString('4 enrichments to pass to failure status because max retries reached', $output);
        $this->assertStringContainsString('1 enrichments to pass to failure status because uploading took so long', $output);
    }
}
