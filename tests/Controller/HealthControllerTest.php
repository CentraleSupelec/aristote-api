<?php

namespace App\Tests\Controller;

use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient(['debug' => false, 'environment' => 'test']);

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testApiHomeCheckRoute(): void
    {
        // Try to access the Api endpoint without being authenticated
        $this->client->request('GET', '/api/v1/home');
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('www-authenticate', 'Bearer');

        // Try to access the Api with token
        $apiClients = ApiClientFixturesProvider::getApiClients($this->entityManager);

        // Request Token without default scope
        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClients[2]->getId(),
            'client_secret' => $apiClients[2]->getSecret(),
        ];
        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        // Call Api Check endpoint without default scope
        $this->client->request('GET', '/api/v1/home', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Call Api Check with sufficient authorization
        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClients[0]->getId(),
            'client_secret' => $apiClients[0]->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', '/api/v1/home', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]
        );
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('OK', $responseData['status']);

        // Call Api Check with expired token
        sleep(2);
        $this->client->request('GET', '/api/v1/home', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testApiHealthCheckRoute(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClients($this->entityManager)[0];

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $responseData['access_token'];

        $this->client->request('GET', '/api/v1/health', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]
        );
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['kernelStatus']);
        $this->assertTrue($responseData['dbConnectionStatus']);
    }

    public function testTokenLastRequestedAtLogic(): void
    {
        $apiClient = ApiClientFixturesProvider::getApiClients($this->entityManager)[0];

        $content = [
            'grant_type' => 'client_credentials',
            'client_id' => $apiClient->getId(),
            'client_secret' => $apiClient->getSecret(),
        ];

        $this->assertNull($apiClient->getTokenLastRequestedAt());

        $dateBeforeRequest = new DateTime();

        $this->client->request('POST', '/api/token', $content);
        $this->assertResponseIsSuccessful();

        $dateAfterRequest = new DateTime();
        $this->assertNotNull($apiClient->getTokenLastRequestedAt());
        $this->assertTrue($dateBeforeRequest <= $apiClient->getTokenLastRequestedAt() && $apiClient->getTokenLastRequestedAt() <= $dateAfterRequest);
    }
}
