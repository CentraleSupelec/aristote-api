<?php

namespace App\Tests\FixturesProvider;

use App\Constants;
use App\Entity\ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Grants;

class ApiClientFixturesProvider
{
    public static function getApiClients(?EntityManagerInterface $entityManager = null): array
    {
        $apiClients = [
            (new ApiClient('Client with default scope', 'default', 'very-secret'))
                ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
                ->setFormExposedScopes([Constants::SCOPE_DEFAULT])
                ->setActive(true),
            (new ApiClient('Deactivated client', 'deactivated', 'deactivated-secret'))
                ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
                ->setFormExposedScopes([Constants::SCOPE_DEFAULT])
                ->setActive(false),
            (new ApiClient('Client without default scope', 'without-default', 'deactivated-secret'))
                ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
                ->setActive(true),
            (new ApiClient('Demo Api Client', 'demo', 'password'))
                ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
                ->setFormExposedScopes([Constants::SCOPE_DEFAULT])
                ->setActive(true),
        ];

        if (null !== $entityManager) {
            foreach ($apiClients as $apiClient) {
                $entityManager->persist($apiClient);
            }

            $entityManager->flush();
        }

        return $apiClients;
    }
}
