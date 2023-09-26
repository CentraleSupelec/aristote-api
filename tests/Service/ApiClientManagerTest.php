<?php

namespace App\Tests\Service;

use App\Constants;
use App\Entity\ApiClient;
use App\Service\ApiClientManager;
use App\Tests\FixturesProvider\ApiClientFixturesProvider;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Grants;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiClientManagerTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected ApiClientManager $apiClientManager;

    protected function setUp(): void
    {
        self::bootKernel([
            'debug' => false,
            'environment' => 'test',
        ]);

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->apiClientManager = static::getContainer()->get(ClientRepositoryInterface::class);
    }

    public function testGetClientEntity(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        /** @var ApiClient $firstClientEntity */
        $firstClientEntity = $this->apiClientManager->getClientEntity('default');

        $this->assertEquals('default', $firstClientEntity->getIdentifier());
        $this->assertEquals([OAuth2Grants::CLIENT_CREDENTIALS], $firstClientEntity->getGrants());
        $this->assertEquals([Constants::SCOPE_DEFAULT], $firstClientEntity->getScopes());
        $this->assertNull($firstClientEntity->getTokenLastRequestedAt());

        $secondClientEntity = $this->apiClientManager->getClientEntity('non-existing');
        $this->assertNull($secondClientEntity);
    }

    public function testUpdateApiClientSecret(): void
    {
        /** @var ApiClient $apiClient */
        $apiClient = ApiClientFixturesProvider::getApiClients()[0];

        $this->assertNotEquals('new-secret-from-live-test', $apiClient->getSecret());

        $apiClient->setPlainSecret('new-secret-from-live-test');
        $this->apiClientManager->updateApiClientSecret($apiClient);

        $this->assertNull($apiClient->getPlainSecret());
        $this->assertEquals('new-secret-from-live-test', $apiClient->getSecret());
    }

    public function testUpdateApiClientSecretNoPlainSecret(): void
    {
        /** @var ApiClient $apiClient */
        $apiClient = ApiClientFixturesProvider::getApiClients()[0];

        $this->assertEquals('very-secret', $apiClient->getSecret());

        $this->apiClientManager->updateApiClientSecret($apiClient);

        $this->assertNull($apiClient->getPlainSecret());
        $this->assertEquals('very-secret', $apiClient->getSecret());
    }

    public function testValidateClient(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        $this->assertTrue(
            $this->apiClientManager->validateClient('default', 'very-secret', 'client_credentials')
        );
    }

    public function testValidateClientWrongSecret(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        $this->assertFalse(
            $this->apiClientManager->validateClient('default', 'wrong-secret', 'client_credentials')
        );
    }

    public function testValidateClientWrongIdentifier(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        $this->assertFalse(
            $this->apiClientManager->validateClient('non-existing', 'very-secret', 'client_credentials')
        );
    }

    public function testValidateClientUnsupportedGrant(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        $this->assertFalse(
            $this->apiClientManager->validateClient('default', 'very-secret', OAuth2Grants::REFRESH_TOKEN)
        );
    }

    public function testValidateClientDeactivatedClient(): void
    {
        ApiClientFixturesProvider::getApiClients($this->entityManager);

        $this->assertFalse(
            $this->apiClientManager->validateClient('deactivated', 'deactivated-secret', 'client_credentials')
        );
    }
}
