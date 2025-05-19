<?php

namespace App\Tests\centralesupelec\Application\Command;

use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentVersionsFixturesProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class FillInitialAndLastEnrichmentVersionTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected KernelInterface $client;

    protected function setUp(): void
    {
        $this->client = self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testFillInitialAndLastEnrichmentVersionCommand()
    {
        $application = new Application($this->client);
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $enrichmentVersion1 = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichmentVersion1->setCreatedAt(new DateTime('-10 minute'));

        $enrichmentVersion2 = EnrichmentVersionsFixturesProvider::addEnrichmentVersion($this->entityManager, $enrichment);
        $enrichmentVersion2->setCreatedAt(new DateTime('-2 minute'));

        $enrichmentVersion3 = EnrichmentVersionsFixturesProvider::addEnrichmentVersion($this->entityManager, $enrichment);

        $this->entityManager->flush();

        $this->assertNull($enrichment->getInitialEnrichmentVersion());
        $this->assertNull($enrichment->getLastEnrichmentVersion());

        $command = $application->find('app:fill-intial-and-last-versions-enrichment');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->entityManager->refresh($enrichment);
        $this->assertNotNull($enrichment->getInitialEnrichmentVersion());
        $this->assertNotNull($enrichment->getLastEnrichmentVersion());

        $this->assertEquals($enrichmentVersion1->getId(), $enrichment->getInitialEnrichmentVersion()->getId());
        $this->assertEquals($enrichmentVersion3->getId(), $enrichment->getLastEnrichmentVersion()->getId());
    }
}
