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

class TranslationWorkerControllerTest extends BaseWebTestCase
{
    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructure1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);
        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructure2(): void
    {
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructure3(): void
    {
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructure4(): void
    {
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $translationInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructureDefault1(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructureDefault2(): void
    {
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructureDefault3(): void
    {
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithNoModelAndNoInfrastructureDefault4(): void
    {
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $translationInfrastructure = (new Infrastructure())->setName('cs');

        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructure1(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructure2(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructure3(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructure4(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationClientModel, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructure5(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationClientModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructureDefault1(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructureDefault2(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructureDefault3(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructureDefault4(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $translationInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationClientModel, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithModelAndNoInfrastructureDefault5(): void
    {
        $translationClientModel = (new AiModel())->setName('CroissantLLM');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationClientModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, $translationClientModel);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModel1(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModel2(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModel3(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModel4(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel, $translationClientInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModel5(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationClientInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, false, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModelDefault1(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModelDefault2(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationModel = (new AiModel())->setName('Llama-3.1');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModelDefault3(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationInfrastructure = (new Infrastructure())->setName('ups');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModelDefault4(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $translationModel = (new AiModel())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, $translationModel, $translationClientInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetTranslationJobWithTranslationApiClientWithInfrastructureAndNoModelDefault5(): void
    {
        $translationClientInfrastructure = (new Infrastructure())->setName('cs');
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager, null, null, null, $translationClientInfrastructure);
        $translationApiClient = ApiClientFixturesProvider::getApiClientScopeTranslationWorker($this->entityManager, true, null, $translationClientInfrastructure);

        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_TRANSLATION);

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $translationApiClient->getId(),
            'client_secret' => $translationApiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', sprintf('/api/v1/enrichments/job/translation/oldest?taskId=%s', (string) Uuid::v7()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['enrichmentId']);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['enrichmentVersionId']);
    }
}
