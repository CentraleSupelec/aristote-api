<?php

namespace App\EventListener;

use App\Entity\ApiClient;
use App\Repository\ApiClientRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Event\TokenRequestResolveEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LastTokenRequestListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiClientRepository $apiClientRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function onLeagueOauth2ServerEventTokenRequestResolve(TokenRequestResolveEvent $tokenRequestResolveEvent): void
    {
        $clientId = (string) $this->requestStack->getCurrentRequest()->request->get('client_id');

        if ('' === $clientId) {
            return;
        }

        $apiClient = $this->apiClientRepository->findOneBy(['identifier' => $clientId]);

        if (!$apiClient instanceof ApiClient) {
            return;
        }

        $apiClient->setTokenLastRequestedAt(new DateTime());
        $this->entityManager->flush();
    }
}
