<?php

namespace App\Tests\FixturesProvider;

use App\Constants;
use App\Entity\AiModel;
use App\Entity\ApiClient;
use App\Entity\Infrastructure;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Grants;

class ApiClientFixturesProvider
{
    /**
     * Get a list of API clients.
     *
     * @param EntityManagerInterface|null
     *
     * @return ApiClient[] an array of ApiClient objects
     */
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
        ];

        if (null !== $entityManager) {
            foreach ($apiClients as $apiClient) {
                $entityManager->persist($apiClient);
            }

            $entityManager->flush();
        }

        return $apiClients;
    }

    public static function getApiClientScopeClient(
        ?EntityManagerInterface $entityManager = null,
        ?AiModel $transcriptionModel = null,
        ?Infrastructure $transcriptionInfrastructure = null,
        ?AiModel $translationModel = null,
        ?Infrastructure $translationInfrastructure = null,
        ?AiModel $enrichmentModel = null,
        ?Infrastructure $enrichmentInfrastructure = null,
    ): ApiClient {
        $apiClient = (new ApiClient('client', 'client', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_CLIENT])
            ->setTranscriptionModel($transcriptionModel)
            ->setTranscriptionInfrastructure($transcriptionInfrastructure)
            ->setTranslationModel($translationModel)
            ->setTranslationInfrastructure($translationInfrastructure)
            ->setEnrichmentModel($enrichmentModel)
            ->setEnrichmentInfrastructure($enrichmentInfrastructure)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($apiClient);

            $entityManager->flush();
        }

        return $apiClient;
    }

    public static function getApiClientScopeClients(?EntityManagerInterface $entityManager = null): array
    {
        $firstApiClient = (new ApiClient('client1', 'client1', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_CLIENT])
        ;

        $secondApiClient = (new ApiClient('client2', 'client2', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_CLIENT])
        ;

        if (null !== $entityManager) {
            $entityManager->persist($firstApiClient);
            $entityManager->persist($secondApiClient);

            $entityManager->flush();
        }

        return [$firstApiClient, $secondApiClient];
    }

    public static function getApiClientScopeEnrichmentWorker(
        ?EntityManagerInterface $entityManager = null,
        bool $treatUnspecifiedModelOrInfrastructure = false,
        ?AiModel $model = null,
        ?Infrastructure $infrastructure = null,
    ): ApiClient {
        $apiClient = (new ApiClient('enrichment', 'enrichment', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_PROCESSING_WORKER])
            ->setAiModel($model)
            ->setInfrastructure($infrastructure)
            ->setTreatUnspecifiedModelOrInfrastructure($treatUnspecifiedModelOrInfrastructure)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($apiClient);
            $entityManager->flush();
        }

        return $apiClient;
    }

    public static function getApiClientScopeTranscriptionWorker(
        ?EntityManagerInterface $entityManager = null,
        bool $treatUnspecifiedModelOrInfrastructure = false,
        ?AiModel $model = null,
        ?Infrastructure $infrastructure = null,
    ): ApiClient {
        $apiClient = (new ApiClient('transcription', 'transcription', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_TRANSCRIPTION_WORKER])
            ->setAiModel($model)
            ->setInfrastructure($infrastructure)
            ->setTreatUnspecifiedModelOrInfrastructure($treatUnspecifiedModelOrInfrastructure)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($apiClient);
            $entityManager->flush();
        }

        return $apiClient;
    }

    public static function getApiClientScopeEvaluationWorker(?EntityManagerInterface $entityManager = null): ApiClient
    {
        $apiClient = (new ApiClient('evaluation', 'evaluation', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_TRANSCRIPTION_WORKER])
        ;

        if (null !== $entityManager) {
            $entityManager->persist($apiClient);
            $entityManager->flush();
        }

        return $apiClient;
    }

    public static function getApiClientScopeTranslationWorker(
        ?EntityManagerInterface $entityManager = null,
        bool $treatUnspecifiedModelOrInfrastructure = false,
        ?AiModel $model = null,
        ?Infrastructure $infrastructure = null,
    ): ApiClient {
        $apiClient = (new ApiClient('translation', 'translation', 'very-secret'))
            ->setActive(true)
            ->setFormExposedGrants([OAuth2Grants::CLIENT_CREDENTIALS])
            ->setFormExposedScopes([Constants::SCOPE_DEFAULT, Constants::SCOPE_TRANSLATION_WORKER])
            ->setAiModel($model)
            ->setInfrastructure($infrastructure)
            ->setTreatUnspecifiedModelOrInfrastructure($treatUnspecifiedModelOrInfrastructure)
        ;

        if (null !== $entityManager) {
            $entityManager->persist($apiClient);
            $entityManager->flush();
        }

        return $apiClient;
    }
}
