<?php

namespace App\Service;

use App\Entity\ApiClient;
use App\Repository\ApiClientRepository;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ApiClientManager implements ClientRepositoryInterface
{
    public function __construct(
        private readonly ApiClientRepository $apiClientRepository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getClientEntity($clientIdentifier): ApiClient|null
    {
        return $this->apiClientRepository->findOneBy(['identifier' => $clientIdentifier]);
    }

    public function updateApiClientSecret(ApiClient $apiClient): void
    {
        if (!$apiClient->getPlainSecret()) {
            return;
        }

        $hashedSecret = $this->passwordHasherFactory->getPasswordHasher($apiClient)->hash($apiClient->getPlainSecret());

        $reflectionClass = new ReflectionClass($apiClient);
        try {
            $reflectionProperty = $reflectionClass->getParentClass()->getProperty('secret');
            $reflectionProperty->setValue($apiClient, $hashedSecret);
        } catch (ReflectionException $exception) {
            $this->logger->error(sprintf('Unable to access ApiClient "secret" property: %s', $exception->getMessage()));
        }

        $apiClient->eraseCredentials();
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        if ('client_credentials' !== $grantType) {
            return false;
        }

        $apiClient = $this->apiClientRepository->findOneBy(['identifier' => $clientIdentifier]);

        if (!$apiClient instanceof ApiClient) {
            return false;
        }

        if (!$apiClient->isActive()) {
            return false;
        }

        if (!$this->isGrantSupported($apiClient, $grantType)) {
            return false;
        }

        return !$apiClient->isConfidential() || $this->validateClientSecret($apiClient, $clientSecret);
    }

    private function isGrantSupported(AbstractClient $client, ?string $grant): bool
    {
        if (null === $grant) {
            return false;
        }

        $grants = $client->getGrants();

        if ([] === $grants) {
            return false;
        }

        return in_array($grant, $grants);
    }

    private function validateClientSecret(ApiClient $apiClient, string $plainTextSecret): bool
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher($apiClient)
            ->verify($apiClient->getSecret(), $plainTextSecret)
        ;
    }
}
