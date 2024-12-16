<?php

namespace App\Tests\Controller\Api;

use App\Entity\AiModel;
use App\Entity\Infrastructure;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\Uid\Uuid;

class TranscriptionControllerTest extends BaseWebTestCase
{
    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructure1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructure2(): void
    {
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructure3(): void
    {
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructure4(): void
    {
        $transcriptionModel = (new AiModel())->setName('whisper');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructureDefault1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructureDefault2(): void
    {
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructureDefault3(): void
    {
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithNoModelAndNoInfrastructureDefault4(): void
    {
        $transcriptionModel = (new AiModel())->setName('whisper');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructure1(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructure2(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructure3(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructure4(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionClientModel, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructure5(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionClientModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, $transcriptionClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructureDefault1(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, $transcriptionClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructureDefault2(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructureDefault3(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructureDefault4(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $transcriptionInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionClientModel, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, $transcriptionClientModel);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithModelAndNoInfrastructureDefault5(): void
    {
        $transcriptionClientModel = (new AiModel())->setName('whisper-v3');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionClientModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, $transcriptionClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModel1(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModel2(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModel3(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModel4(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel, $transcriptionClientInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModel5(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionClientInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, false, null, $transcriptionClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModelDefault1(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, null, $transcriptionClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModelDefault2(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionModel = (new AiModel())->setName('whisper');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModelDefault3(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModelDefault4(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $transcriptionModel = (new AiModel())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, $transcriptionModel, $transcriptionClientInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, null, $transcriptionClientInfrastructure);

        EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranscriptionJobWithTranscriptionApiClientWithInfrastructureAndNoModelDefault5(): void
    {
        $transcriptionClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, $transcriptionClientInfrastructure);
        $transcriptionApiClient = ApiClientFixturesProvider::getApiClientScopeTranscriptionWorker($this->entityManager, true, null, $transcriptionClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $transcriptionApiClient->getId(),
            'client_secret' => $transcriptionApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/transcription/job/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
    }
}
