<?php

namespace App\Tests\Controller\Api;

use App\Entity\AiModel;
use App\Entity\Enrichment;
use App\Entity\Infrastructure;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentVersionsFixturesProvider;
use App\Tests\Utils\BaseWebTestCase;
use Symfony\Component\Uid\Uuid;

class AiEnrichmentsWorkerControllerTest extends BaseWebTestCase
{
    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructure1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);
        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructure2(): void
    {
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructure3(): void
    {
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructure4(): void
    {
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName())->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructureDefault1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructureDefault2(): void
    {
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructureDefault3(): void
    {
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithNoModelAndNoInfrastructureDefault4(): void
    {
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName())->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructure1(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructure2(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructure3(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructure4(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentClientModel, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentClientModel->getName())->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructure5(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentClientModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentClientModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructureDefault1(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructureDefault2(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructureDefault3(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructureDefault4(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $enrichmentInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentClientModel, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentClientModel->getName())->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithModelAndNoInfrastructureDefault5(): void
    {
        $enrichmentClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentClientModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, $enrichmentClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModel1(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModel2(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModel3(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModel4(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel, $enrichmentClientInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName())->setInfrastructure($enrichmentClientInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModel5(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentClientInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, false, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentClientInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModelDefault1(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModelDefault2(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModelDefault3(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setInfrastructure($enrichmentInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModelDefault4(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $enrichmentModel = (new AiModel())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $enrichmentModel, $enrichmentClientInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setAiModel($enrichmentModel->getName())->setInfrastructure($enrichmentClientInfrastructure->getName());

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEnrichmentJobWithEnrichmentApiClientWithInfrastructureAndNoModelDefault5(): void
    {
        $enrichmentClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $enrichmentClientInfrastructure);
        $enrichmentApiClient = ApiClientFixturesProvider::getApiClientScopeEnrichmentWorker($this->entityManager, true, null, $enrichmentClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $enrichmentApiClient->getId(),
            'client_secret' => $enrichmentApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/ai_enrichment/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }
}
