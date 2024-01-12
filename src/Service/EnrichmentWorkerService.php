<?php

namespace App\Service;

use App\Exception\AristoteApiAuthenticationException;
use App\Exception\AristoteApiException;
use DateInterval;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EnrichmentWorkerService
{
    private ?string $token = null;
    private ?DateTime $tokenExpiration = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $uriPrefix,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    public function __destruct()
    {
        $this->clearToken();
    }

    /**
     * @throws AristoteApiAuthenticationException
     */
    public function regenerateSession(): void
    {
        $this->clearToken();
        $this->initToken();
    }

    public function apiRequestWithToken(
        string $method,
        string $uri = '',
        array $options = [],
        int $maxRetries = 1,
        int $retryDelayMs = 250,
        array $successCodes = [Response::HTTP_OK],
    ): ResponseInterface|null {
        $versionedUri = preg_match('/^\/api\/v(\d+\.\d+)/', $uri) ? $uri : sprintf('/api/v1%s', $uri);

        $prefixedVersionedUri = $this->uriPrefix.$versionedUri;

        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }
        $this->initToken();
        $options['headers'] += [
            'Accept' => 'application/json',
        ];

        $retryCount = 0;
        $success = false;
        do {
            try {
                $this->initToken();
                $options['headers']['Authorization'] = sprintf('Bearer %s', $this->token);

                $response = $this->httpClient->request($method, $prefixedVersionedUri, $options);

                if (!in_array($response->getStatusCode(), $successCodes)) {
                    $errors = $response->toArray(false)['errors'] ?? [];
                    throw new AristoteApiException(sprintf('Errors returned by Aristote API :[ %s ]', implode(', ', $errors)));
                } else {
                    $success = true;
                }
            } catch (AristoteApiException|TransportExceptionInterface $exception) {
                $this->logger->error(sprintf('Error while requesting AristoteApi: %s', $exception));

                if ($retryCount >= $maxRetries) {
                    throw new AristoteApiException($exception->getMessage(), $exception->getCode(), $exception);
                }
            }

            $retry = $retryCount < $maxRetries && !$success;
            ++$retryCount;

            if ($retry) {
                $this->clearToken();
                usleep($retryDelayMs * 1000);
            }
        } while ($retry);

        return $response ?? null;
    }

    /**
     * @throws AristoteApiAuthenticationException
     */
    private function initToken(): void
    {
        if (null === $this->token) {
            try {
                $response = $this->httpClient->request('POST', sprintf('%s/api/token', $this->uriPrefix), [
                    'headers' => [
                            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                        ],
                        'body' => [
                            'grant_type' => 'client_credentials',
                        ],
                ]);

                if (200 !== $response->getStatusCode()) {
                    throw new AristoteApiAuthenticationException('Failed to request AristoteApi authentication token.');
                }
            } catch (TransportExceptionInterface $exception) {
                throw new AristoteApiAuthenticationException(sprintf('Failed to request AristoteApi authentication token : %s', $exception->getMessage()), $exception->getCode(), $exception);
            }
            try {
                $jsonResponse = $response->toArray();
            } catch (
                ClientExceptionInterface|
                RedirectionExceptionInterface|
                ServerExceptionInterface|
                TransportExceptionInterface|
                DecodingExceptionInterface $exception
            ) {
                throw new AristoteApiAuthenticationException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if (empty($jsonResponse['access_token'])) {
                throw new AristoteApiAuthenticationException('Token was not found in AristoteApi authentication response.');
            }
            $this->token = $jsonResponse['access_token'];
            $this->tokenExpiration = new DateTime();
            try {
                $this->tokenExpiration->add(new DateInterval(sprintf('PT%dS', $jsonResponse['expires_in'])));
            } catch (Exception $exception) {
                $this->logger->warning(sprintf('Failed to set token expiration date: %s', $exception->getMessage()));
            }
            $this->logger->info('Received new AristoteApi token.');
        } elseif ($this->tokenExpiration < new DateTime()) {
            $this->logger->info('Bearer Token expired, retrieving a new token.');
            $this->regenerateSession();
        }
    }

    public function clearToken(): void
    {
        $this->token = null;
        $this->tokenExpiration = null;
    }
}
