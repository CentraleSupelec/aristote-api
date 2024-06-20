<?php

namespace App\Tests\Controller\Api;

use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentVersionsFixturesProvider;
use App\Tests\Utils\BaseWebTestCase;

class EnrichmentControllerTest extends BaseWebTestCase
{
    public function testGetEnrichments(): void
    {
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichment->getCreatedBy()->getId(),
            'client_secret' => $enrichment->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $responseData['content']);
    }

    public function testGetEnrichmentsWithWrongApiClient(): void
    {
        EnrichmentFixturesProvider::getEnrichment($this->entityManager);
        $otherApiClient = ApiClientFixturesProvider::getApiClientScopeClients($this->entityManager)[0];

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $otherApiClient->getId(),
            'client_secret' => $otherApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(0, $responseData['content']);
    }

    public function testGetEnrichment(): void
    {
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichment->getCreatedBy()->getId(),
            'client_secret' => $enrichment->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['id']);
    }

    public function testGetEnrichmentWithWrongClient(): void
    {
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager);
        $otherApiClient = ApiClientFixturesProvider::getApiClientScopeClients($this->entityManager)[0];

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $otherApiClient->getId(),
            'client_secret' => $otherApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteEnrichment(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichment = $enrichmentVersion->getEnrichment();

        $this->assertNotTrue($enrichment->isDeleted());
        $this->assertNotNull($enrichment->getNotificationWebhookUrl());
        $this->assertIsArray($enrichment->getDisciplines());
        $this->assertIsArray($enrichment->getMediaTypes());
        $this->assertNotNull($enrichment->getMedia()->getOriginalFileName());

        $this->assertCount(1, $enrichment->getVersions());

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichment->getCreatedBy()->getId(),
            'client_secret' => $enrichment->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['id']);

        $this->client->request('DELETE', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseStatusCodeSame(404);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $enrichment->getId());

        $this->assertTrue($enrichment->isDeleted());
        $this->assertNull($enrichment->getNotificationWebhookUrl());
        $this->assertNull($enrichment->getDisciplines());
        $this->assertNull($enrichment->getMediaTypes());
        $this->assertNull($enrichment->getMedia()->getOriginalFileName());
        $this->assertCount(0, $enrichment->getVersions());

        $transcript = $this->entityManager->find(Transcript::class, $enrichmentVersion->getTranscript()->getId());
        $this->assertNull($transcript);

        $enrichmentVersionMetadata = $this->entityManager->find(EnrichmentVersionMetadata::class, $enrichmentVersion->getEnrichmentVersionMetadata()->getId());
        $this->assertNull($enrichmentVersionMetadata);

        $firstMcq = $this->entityManager->find(MultipleChoiceQuestion::class, $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId());
        $this->assertNull($firstMcq);

        $enrichmentVersion = $this->entityManager->find(EnrichmentVersion::class, $enrichmentVersion->getId());
        $this->assertNull($enrichmentVersion);
    }

    public function testGetEnrichmentVersions(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getId(),
            'client_secret' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions', $enrichmentVersion->getEnrichment()->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['content']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['content'][0]['id']);
    }

    public function testGetLatestEnrichmentVersion(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getId(),
            'client_secret' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions', $enrichmentVersion->getEnrichment()->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['content']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['content'][0]['id']);
    }

    public function testGetEnrichmentVersion(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getId(),
            'client_secret' => $enrichmentVersion->getEnrichment()->getCreatedBy()->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions/%s', $enrichmentVersion->getEnrichment()->getId(), $enrichmentVersion->getId()), [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['id']);
    }
}