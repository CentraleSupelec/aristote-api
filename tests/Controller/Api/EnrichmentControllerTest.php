<?php

namespace App\Tests\Controller\Api;

use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Repository\ChoiceRepository;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use App\Tests\FixturesProvider\EnrichmentVersionsFixturesProvider;
use App\Tests\Utils\BaseWebTestCase;
use DateTime;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EnrichmentControllerTest extends BaseWebTestCase
{
    public function testGetEnrichments(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);
        $enrichments = [];
        for ($i = 0; $i < 5; ++$i) {
            $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager, $apiClient);
            $enrichment->setCreatedAt(new DateTime(sprintf('-%s minute', $i)));
            $enrichments[] = $enrichment;
        }

        $this->entityManager->flush();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(count($enrichments), $responseData['content']);
        $this->assertEquals($enrichments[0]->getId(), $responseData['content'][0]['id']);
        $this->assertEquals($enrichments[4]->getId(), $responseData['content'][4]['id']);

        $size = 0;
        $this->client->request('GET', sprintf('/api/v1/enrichments?size=%s', $size), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);

        $page = 0;
        $this->client->request('GET', sprintf('/api/v1/enrichments?page=%s', $size), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);

        $size = 2;
        $this->client->request('GET', sprintf('/api/v1/enrichments?size=%s', $size), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount($size, $responseData['content']);
        $this->assertEquals($enrichments[0]->getId(), $responseData['content'][0]['id']);

        $size = 2;
        $page = 2;
        $this->client->request('GET', sprintf('/api/v1/enrichments?size=%s&page=%s', $size, $page), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount($size, $responseData['content']);
        $this->assertEquals($enrichments[2]->getId(), $responseData['content'][0]['id']);

        $this->client->request('GET', '/api/v1/enrichments?order=ASC', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(count($enrichments), $responseData['content']);
        $this->assertEquals($enrichments[4]->getId(), $responseData['content'][0]['id']);
        $this->assertArrayNotHasKey('latestEnrichmentRequestedAt', $responseData['content'][0]);

        $this->client->request('GET', '/api/v1/enrichments?sort=status', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments?sort=updatedAt', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments?sort=createdAt', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/enrichments?sort=aiModel', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);

        $this->client->request('GET', '/api/v1/enrichments?withStatus=true', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($responseData['content'][0]['latestEnrichmentRequestedAt']);

        $this->client->request('GET', '/api/v1/enrichments?order=des', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichment->getId(), $responseData['id']);

        $this->client->request('DELETE', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
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
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager);
        $enrichmentVersions = [];

        for ($i = 0; $i < 5; ++$i) {
            $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager, $enrichment);
            $enrichmentVersion->setCreatedAt(new DateTime(sprintf('-%s minute', $i)));
            $enrichmentVersions[] = $enrichmentVersion;
        }

        $this->entityManager->flush();

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

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $responseData['content']);
        $this->assertEquals($enrichmentVersions[0]->getId(), $responseData['content'][0]['id']);

        $size = 10;
        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?size=%s', $enrichment->getId(), $size), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(count($enrichmentVersions), $responseData['content']);
        $this->assertEquals($enrichmentVersions[0]->getId(), $responseData['content'][0]['id']);
        $this->assertEquals($enrichmentVersions[4]->getId(), $responseData['content'][4]['id']);

        $size = 2;
        $page = 2;
        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?size=%s&page=%s', $enrichment->getId(), $size, $page), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount($size, $responseData['content']);
        $this->assertEquals($enrichmentVersions[2]->getId(), $responseData['content'][0]['id']);

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?order=ASC&size=10', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(count($enrichmentVersions), $responseData['content']);
        $this->assertEquals($enrichmentVersions[4]->getId(), $responseData['content'][0]['id']);
        $this->assertNotNull($responseData['content'][0]['transcript']);

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?sort=createdAt', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?sort=updatedAt', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?sort=status', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);

        $this->client->request('GET', sprintf('/api/v1/enrichments/%s/versions?withTranscript=false', $enrichment->getId()), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('transcript', $responseData['content'][0]);

        $this->client->request('GET', '/api/v1/enrichments/%s/versions?order=des', [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
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
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($enrichmentVersion->getId(), $responseData['id']);
    }

    public function testCreateEnrichmentVersionNoInitialVersion(): void
    {
        $enrichment = EnrichmentFixturesProvider::getEnrichment($this->entityManager);
        $apiClient = $enrichment->getCreatedBy();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'title' => 'Title',
            'description' => 'Description',
            'discipline' => 'Discipline',
            'mediaType' => 'MediaType',
            'topics' => [],
            'translatedTopics' => [],
        ];

        $multipleChoiceQuestions = [
            [
                'question' => 'Question 1',
                'translatedQuestion' => 'Question 1 translated',
                'explanation' => 'Explanation',
                'translatedExplanation' => 'Explanation translated',
                'answerPointer' => [
                    'startAnswerPointer' => '',
                    'stopAnswerPointer' => '',
                ],
                'choices' => [
                    [
                        'optionText' => 'Choice 1',
                        'correctAnswer' => true,
                        'translatedOptionText' => 'Choice 1 translated',
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => json_encode($enrichmentVersionMetadata, JSON_PRETTY_PRINT),
            'multipleChoiceQuestions' => json_encode($multipleChoiceQuestions, JSON_PRETTY_PRINT),
        ];

        $this->client->request('POST', sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()), $content, [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateEnrichmentVersionWithoutTranscript(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichmentVersion->setCreatedAt(new DateTime('-2 minute'));
        $this->entityManager->flush();

        $enrichment = $enrichmentVersion->getEnrichment();
        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentVersionRepository $enrichmentVersionRepository */
        $enrichmentVersionRepository = $this->entityManager->getRepository(EnrichmentVersion::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'title' => 'Title',
            'description' => 'Description',
            'discipline' => 'Discipline',
            'mediaType' => 'MediaType',
            'topics' => [],
            'translatedTopics' => [],
        ];

        $multipleChoiceQuestions = [
            [
                'question' => 'Question 1',
                'translatedQuestion' => 'Question 1 translated',
                'explanation' => 'Explanation',
                'translatedExplanation' => 'Explanation translated',
                'answerPointer' => [
                    'startAnswerPointer' => '',
                    'stopAnswerPointer' => '',
                ],
                'choices' => [
                    [
                        'optionText' => 'Choice 1',
                        'correctAnswer' => true,
                        'translatedOptionText' => 'Choice 1 translated',
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => json_encode($enrichmentVersionMetadata, JSON_PRETTY_PRINT),
            'multipleChoiceQuestions' => json_encode($multipleChoiceQuestions, JSON_PRETTY_PRINT),
        ];

        $this->client->request(
            'POST',
            sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()),
            $content,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();

        $newEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichment->getId());
        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentId($enrichment->getId(), 1, 5, 'createdAt', 'desc');

        $this->assertNotNull($newEnrichmentVersion);
        $this->assertCount(2, $enrichmentVersions->getItems());
        $this->assertEquals($enrichmentVersions->offsetGet(0)->getId(), $newEnrichmentVersion->getId());
        $this->assertNotEquals($enrichmentVersions->offsetGet(1)->getId(), $newEnrichmentVersion->getId());

        $this->assertEquals($enrichmentVersionMetadata['title'], $newEnrichmentVersion->getEnrichmentVersionMetadata()->getTitle());
        $this->assertEquals($multipleChoiceQuestions[0]['question'], $newEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getQuestion());
        $this->assertEquals($enrichmentVersions->offsetGet(1)->getTranscript()->getText(), $newEnrichmentVersion->getTranscript()->getText());
    }

    public function testCreateEnrichmentVersionWithTranscript(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichmentVersion->setCreatedAt(new DateTime('-2 minute'));
        $enrichment = $enrichmentVersion->getEnrichment();

        $this->entityManager->flush();

        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentVersionRepository $enrichmentVersionRepository */
        $enrichmentVersionRepository = $this->entityManager->getRepository(EnrichmentVersion::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'title' => 'Title',
            'description' => 'Description',
            'discipline' => 'Discipline',
            'mediaType' => 'MediaType',
            'topics' => [],
            'translatedTopics' => [],
        ];
        $multipleChoiceQuestions = [
            [
                'question' => 'Question 1',
                'translatedQuestion' => 'Question 1 translated',
                'explanation' => 'Explanation',
                'translatedExplanation' => 'Explanation translated',
                'answerPointer' => [
                    'startAnswerPointer' => '',
                    'stopAnswerPointer' => '',
                ],
                'choices' => [
                    [
                        'optionText' => 'Choice 1',
                        'correctAnswer' => true,
                        'translatedOptionText' => 'Choice 1 translated',
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => json_encode($enrichmentVersionMetadata, JSON_PRETTY_PRINT),
            'multipleChoiceQuestions' => json_encode($multipleChoiceQuestions, JSON_PRETTY_PRINT),
        ];

        $transcript = [
            'language' => 'en',
            'text' => 'Hello World !',
            'sentences' => [
                [
                    'text' => 'Hello World !',
                    'start' => 0,
                    'end' => 1,
                ],
            ],
        ];

        $tempFilePath = tempnam(sys_get_temp_dir(), 'transcript_');
        file_put_contents($tempFilePath, json_encode($transcript, JSON_PRETTY_PRINT));

        $files = [
            'transcript' => new UploadedFile(
                $tempFilePath,
                'transcript.json',
                'application/json',
                null,
                true
            ),
        ];

        $this->client->request(
            'POST',
            sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()),
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();
        unlink($tempFilePath);

        $newEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichment->getId());
        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentId($enrichment->getId(), 1, 5, 'createdAt', 'desc');

        $this->assertNotNull($newEnrichmentVersion);
        $this->assertCount(2, $enrichmentVersions->getItems());
        $this->assertEquals($enrichmentVersions->offsetGet(0)->getId(), $newEnrichmentVersion->getId());
        $this->assertNotEquals($enrichmentVersions->offsetGet(1)->getId(), $newEnrichmentVersion->getId());

        $this->assertEquals($enrichmentVersionMetadata['title'], $newEnrichmentVersion->getEnrichmentVersionMetadata()->getTitle());
        $this->assertEquals($multipleChoiceQuestions[0]['question'], $newEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getQuestion());
        $this->assertEquals($transcript['text'], $newEnrichmentVersion->getTranscript()->getText());
    }

    public function testCreateEnrichmentVersionRequestTranslation(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichmentVersion->setCreatedAt(new DateTime('-2 minute'));
        $enrichment = $enrichmentVersion->getEnrichment();

        $this->entityManager->flush();

        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentRepository $enrichmentRepository */
        $enrichmentRepository = $this->entityManager->getRepository(Enrichment::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'title' => 'Title',
            'description' => 'Description',
            'discipline' => 'Discipline',
            'mediaType' => 'MediaType',
            'topics' => [],
            'translatedTopics' => [],
        ];
        $multipleChoiceQuestions = [
            [
                'question' => 'Question 1',
                'translatedQuestion' => 'Question 1 translated',
                'explanation' => 'Explanation',
                'translatedExplanation' => 'Explanation translated',
                'answerPointer' => [
                    'startAnswerPointer' => '',
                    'stopAnswerPointer' => '',
                ],
                'choices' => [
                    [
                        'optionText' => 'Choice 1',
                        'correctAnswer' => true,
                        'translatedOptionText' => 'Choice 1 translated',
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => json_encode($enrichmentVersionMetadata, JSON_PRETTY_PRINT),
            'multipleChoiceQuestions' => json_encode($multipleChoiceQuestions, JSON_PRETTY_PRINT),
            'translate' => true,
        ];

        $this->client->request(
            'POST',
            sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()),
            $content,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();

        $updatedEnrichment = $enrichmentRepository->findOneBy(['id' => $enrichment->getId()]);

        $this->assertNotNull($updatedEnrichment);
        $this->assertEquals(Enrichment::STATUS_WAITING_TRANSLATION, $updatedEnrichment->getStatus());
    }

    public function testCreateEnrichmentVersionRequestNoTranslation(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichmentVersion->setCreatedAt(new DateTime('-2 minute'));
        $enrichment = $enrichmentVersion->getEnrichment();

        $this->entityManager->flush();

        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentRepository $enrichmentRepository */
        $enrichmentRepository = $this->entityManager->getRepository(Enrichment::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'title' => 'Title',
            'description' => 'Description',
            'discipline' => 'Discipline',
            'mediaType' => 'MediaType',
            'topics' => [],
            'translatedTopics' => [],
        ];
        $multipleChoiceQuestions = [
            [
                'question' => 'Question 1',
                'translatedQuestion' => 'Question 1 translated',
                'explanation' => 'Explanation',
                'translatedExplanation' => 'Explanation translated',
                'answerPointer' => [
                    'startAnswerPointer' => '',
                    'stopAnswerPointer' => '',
                ],
                'choices' => [
                    [
                        'optionText' => 'Choice 1',
                        'correctAnswer' => true,
                        'translatedOptionText' => 'Choice 1 translated',
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => json_encode($enrichmentVersionMetadata, JSON_PRETTY_PRINT),
            'multipleChoiceQuestions' => json_encode($multipleChoiceQuestions, JSON_PRETTY_PRINT),
            'translate' => false,
        ];

        $this->client->request(
            'POST',
            sprintf('/api/v1/enrichments/%s/versions', $enrichment->getId()),
            $content,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();

        $updatedEnrichment = $enrichmentRepository->findOneBy(['id' => $enrichment->getId()]);

        $this->assertNotNull($updatedEnrichment);
        $this->assertEquals(Enrichment::STATUS_SUCCESS, $updatedEnrichment->getStatus());
    }

    public function testCreateEnrichmentByUrl(): void
    {
        $apiCient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiCient->getId(),
            'client_secret' => $apiCient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'endUserIdentifier' => 'test@gmail.com',
            'notificationWebhookUrl' => 'http://localhost.fr/api/webhook',
            'url' => 'http://localhost.fr/video.mp4',
            'enrichmentParameters' => [],
        ];

        $this->client->request('POST', '/api/v1/enrichments/url', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($content));

        $this->assertResponseStatusCodeSame(400);

        $content['enrichmentParameters']['generateMetadata'] = false;

        $this->client->request('POST', '/api/v1/enrichments/url', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($content));

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $responseData['id']);
        $this->assertInstanceOf(Enrichment::class, $enrichment);
    }

    public function testCreateEnrichmentByVideoFile(): void
    {
        $apiCient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiCient->getId(),
            'client_secret' => $apiCient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'endUserIdentifier' => 'test@gmail.com',
            'notificationWebhookUrl' => 'http://localhost.fr/api/webhook',
            'enrichmentParameters' => json_encode([]),
        ];

        $tempFilePath = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFilePath, '');

        $files = [
            'file' => new UploadedFile(
                $tempFilePath,
                'video.mp4',
                'video/mp4',
                null,
                true
            ),
        ];

        $this->client->request(
            'POST',
            '/api/v1/enrichments/upload',
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $content['enrichmentParameters'] = json_encode([
            'generateMetadata' => false,
        ]);

        $this->client->request(
            'POST',
            '/api/v1/enrichments/upload',
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );

        $this->assertResponseIsSuccessful();
        unlink($tempFilePath);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $responseData['id']);
        $this->assertInstanceOf(Enrichment::class, $enrichment);
    }

    public function testCreateEnrichmentByAudioFile(): void
    {
        $apiCient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiCient->getId(),
            'client_secret' => $apiCient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'endUserIdentifier' => 'test@gmail.com',
            'notificationWebhookUrl' => 'http://localhost.fr/api/webhook',
            'enrichmentParameters' => json_encode([
                'disciplines' => [
                    'Maths',
                    'Physics',
                ],
                'mediaTypes' => [
                    'Conference',
                    'Course',
                ],
                'aiEvaluation' => 'ChatGPT',
            ]),
        ];

        $tempFilePath = tempnam(sys_get_temp_dir(), 'audio_');
        file_put_contents($tempFilePath, '');

        $files = [
            'file' => new UploadedFile(
                $tempFilePath,
                'audio.mp3',
                'audio/mp3',
                null,
                true
            ),
        ];

        $this->client->request(
            'POST',
            '/api/v1/enrichments/upload',
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();
        unlink($tempFilePath);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $responseData['id']);
        $this->assertInstanceOf(Enrichment::class, $enrichment);
    }

    public function testCreateEnrichmentBySrtFile(): void
    {
        $apiCient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiCient->getId(),
            'client_secret' => $apiCient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'endUserIdentifier' => 'test@gmail.com',
            'notificationWebhookUrl' => 'http://localhost.fr/api/webhook',
            'enrichmentParameters' => json_encode([
                'disciplines' => [
                    'Maths',
                    'Physics',
                ],
                'mediaTypes' => [
                    'Conference',
                    'Course',
                ],
                'aiEvaluation' => 'ChatGPT',
            ]),
        ];

        $subtitle = "1\n00:00:00,000 --> 00:00:01,000\nHello World !";

        $tempFilePath = tempnam(sys_get_temp_dir(), 'subtitle_');
        file_put_contents($tempFilePath, $subtitle);

        $files = [
            'file' => new UploadedFile(
                $tempFilePath,
                'subtitle.srt',
                'text/plain',
                null,
                true
            ),
        ];

        $this->client->request(
            'POST',
            '/api/v1/enrichments/upload',
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();
        unlink($tempFilePath);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $responseData['id']);
        $this->assertInstanceOf(Enrichment::class, $enrichment);
    }

    public function testCreateEnrichmentByVttFile(): void
    {
        $apiCient = ApiClientFixturesProvider::getApiClientScopeClient($this->entityManager);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiCient->getId(),
            'client_secret' => $apiCient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'endUserIdentifier' => 'test@gmail.com',
            'notificationWebhookUrl' => 'http://localhost.fr/api/webhook',
            'enrichmentParameters' => json_encode([
                'disciplines' => [
                    'Maths',
                    'Physics',
                ],
                'mediaTypes' => [
                    'Conference',
                    'Course',
                ],
                'aiEvaluation' => 'ChatGPT',
            ]),
        ];

        $subtitle = "WEBVTT\n\n00:00:00.000 --> 00:00:01.000\nHello World !";

        $tempFilePath = tempnam(sys_get_temp_dir(), 'subtitle_');
        file_put_contents($tempFilePath, $subtitle);

        $files = [
            'file' => new UploadedFile(
                $tempFilePath,
                'subtitle.vtt',
                'text/plain',
                null,
                true
            ),
        ];

        $this->client->request(
            'POST',
            '/api/v1/enrichments/upload',
            $content,
            $files,
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );
        $this->assertResponseIsSuccessful();
        unlink($tempFilePath);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        /** @var Enrichment $enrichment */
        $enrichment = $this->entityManager->find(Enrichment::class, $responseData['id']);
        $this->assertInstanceOf(Enrichment::class, $enrichment);
    }

    public function testEvaluateEnrichmentVersion(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichment = $enrichmentVersion->getEnrichment();

        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentVersionRepository $enrichmentRepository */
        $enrichmentVersionRepository = $this->entityManager->getRepository(EnrichmentVersion::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $enrichmentVersionMetadata = [
            'thumbUpTitle' => true,
            'thumbUpDescription' => true,
            'thumbUpDiscipline' => true,
            'thumbUpMediaType' => false,
            'thumbUpTopics' => true,
            'userFeedback' => 'Good enrichment',
        ];
        $multipleChoiceQuestions = [
            [
                'id' => $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId(),
                'thumbUp' => true,
                'userFeedback' => 'Good question',
                'choices' => [
                    [
                        'id' => $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(0)->getId(),
                        'thumbUp' => true,
                    ],

                    [
                        'id' => $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(1)->getId(),
                        'thumbUp' => false,
                    ],
                ],
            ],
        ];

        $content = [
            'enrichmentVersionMetadata' => $enrichmentVersionMetadata,
            'multipleChoiceQuestions' => $multipleChoiceQuestions,
        ];

        $this->client->request(
            'POST',
            sprintf('/api/v1/enrichments/%s/versions/%s/evaluate', $enrichment->getId(), $enrichmentVersion->getId()),
            content: json_encode($content, JSON_PRETTY_PRINT),
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'application/json',
            ],
        );
        $this->assertResponseIsSuccessful();

        $updatedEnrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $enrichmentVersion->getId()]);

        $this->assertEquals($enrichmentVersionMetadata['thumbUpTitle'], $updatedEnrichmentVersion->getEnrichmentVersionMetadata()->getThumbUpTitle());
        $this->assertEquals($enrichmentVersionMetadata['thumbUpMediaType'], $updatedEnrichmentVersion->getEnrichmentVersionMetadata()->getThumbUpMediaType());
        $this->assertEquals($enrichmentVersionMetadata['thumbUpDescription'], $updatedEnrichmentVersion->getEnrichmentVersionMetadata()->getThumbUpDescription());
        $this->assertEquals($enrichmentVersionMetadata['userFeedback'], $updatedEnrichmentVersion->getEnrichmentVersionMetadata()->getUserFeedback());

        $this->assertEquals($multipleChoiceQuestions[0]['id'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId());
        $this->assertEquals($multipleChoiceQuestions[0]['thumbUp'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getThumbUp());
        $this->assertEquals($multipleChoiceQuestions[0]['userFeedback'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getUserFeedback());

        $this->assertEquals($multipleChoiceQuestions[0]['choices'][0]['id'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(0)->getId());
        $this->assertEquals($multipleChoiceQuestions[0]['choices'][0]['thumbUp'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(0)->getThumbUp());
        $this->assertEquals($multipleChoiceQuestions[0]['choices'][1]['id'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(1)->getId());
        $this->assertEquals($multipleChoiceQuestions[0]['choices'][1]['thumbUp'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(1)->getThumbUp());
    }

    public function testEvaluateMultipleChoiceQuestion(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichment = $enrichmentVersion->getEnrichment();

        $apiClient = $enrichment->getCreatedBy();

        /** @var EnrichmentVersionRepository $enrichmentRepository */
        $enrichmentVersionRepository = $this->entityManager->getRepository(EnrichmentVersion::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'thumbUp' => true,
            'userFeedback' => 'Excellent question',
        ];

        $this->client->request(
            'POST',
            sprintf(
                '/api/v1/enrichments/%s/versions/%s/mcq/%s',
                $enrichment->getId(),
                $enrichmentVersion->getId(),
                $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId()
            ),
            content: json_encode($content, JSON_PRETTY_PRINT),
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'application/json',
            ],
        );
        $this->assertResponseIsSuccessful();

        $updatedEnrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $enrichmentVersion->getId()]);

        $this->assertEquals($enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId(), $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId());
        $this->assertEquals($content['thumbUp'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getThumbUp());
        $this->assertEquals($content['userFeedback'], $updatedEnrichmentVersion->getMultipleChoiceQuestions()->get(0)->getUserFeedback());
    }

    public function testEvaluateChoice(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $enrichment = $enrichmentVersion->getEnrichment();

        $apiClient = $enrichment->getCreatedBy();

        /** @var ChoiceRepository $choiceRepository */
        $choiceRepository = $this->entityManager->getRepository(Choice::class);

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $content = [
            'thumbUp' => true,
        ];

        $this->client->request(
            'POST',
            sprintf(
                '/api/v1/enrichments/%s/versions/%s/mcq/%s/choice/%s',
                $enrichment->getId(),
                $enrichmentVersion->getId(),
                $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getId(),
                $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(0)->getId()
            ),
            content: json_encode($content, JSON_PRETTY_PRINT),
            server: [
                'HTTP_Authorization' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'application/json',
            ],
        );
        $this->assertResponseIsSuccessful();

        $choice = $choiceRepository->findOneBy(['id' => $enrichmentVersion->getMultipleChoiceQuestions()->get(0)->getChoices()->get(0)->getId()]);
        $this->assertEquals($content['thumbUp'], $choice->getThumbUp());
    }

    public function testDownloadTranscript(): void
    {
        $enrichmentVersion = EnrichmentVersionsFixturesProvider::getEnrichmentVersion($this->entityManager);
        $apiClient = $enrichmentVersion->getEnrichment()->getCreatedBy();

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', sprintf(
            '/api/v1/enrichments/%s/versions/%s/download_transcript',
            $enrichmentVersion->getEnrichment()->getId(),
            $enrichmentVersion->getId()
        ), [], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $contentDisposition = $this->client->getResponse()->headers->get('content-disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringEndsWith('.srt', $contentDisposition);

        $this->client->request('GET', sprintf(
            '/api/v1/enrichments/%s/versions/%s/download_transcript',
            $enrichmentVersion->getEnrichment()->getId(),
            $enrichmentVersion->getId()
        ), [
            'format' => 'vtt',
        ], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $contentDisposition = $this->client->getResponse()->headers->get('content-disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringEndsWith('.vtt', $contentDisposition);

        $this->client->request('GET', sprintf(
            '/api/v1/enrichments/%s/versions/%s/download_transcript',
            $enrichmentVersion->getEnrichment()->getId(),
            $enrichmentVersion->getId()
        ), [
            'format' => 'wrong',
        ], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);

        $this->client->request('GET', sprintf(
            '/api/v1/enrichments/%s/versions/%s/download_transcript',
            $enrichmentVersion->getEnrichment()->getId(),
            $enrichmentVersion->getId()
        ), [
            'language' => 'fr',
        ], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $contentDisposition = $this->client->getResponse()->headers->get('content-disposition');
        $this->assertNotNull($contentDisposition);

        $this->client->request('GET', sprintf(
            '/api/v1/enrichments/%s/versions/%s/download_transcript',
            $enrichmentVersion->getEnrichment()->getId(),
            $enrichmentVersion->getId()
        ), [
            'language' => 'en',
        ], [], [
            'HTTP_Authorization' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public static function tearDownAfterClass(): void
    {
        $testUploadsDir = __DIR__.'/../../../var/test_uploads';

        if (is_dir($testUploadsDir)) {
            // Recursively remove the directory and its contents
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($testUploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($testUploadsDir);
        }
    }
}
