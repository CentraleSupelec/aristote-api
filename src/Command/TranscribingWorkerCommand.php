<?php

namespace App\Command;

use App\Exception\AristoteApiException;
use App\Service\EnrichmentWorkerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(name: 'app:transcribing-worker', description: 'Generates a transcription')]
class TranscribingWorkerCommand extends Command
{
    public function __construct(
        private readonly EnrichmentWorkerService $enrichmentWorkerService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Get a job from AristoteApi and treat it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $taskId = Uuid::v7();

        $response = $this->enrichmentWorkerService->apiRequestWithToken(
            'GET',
            '/enrichments/transcription/job/oldest',
            options: [
                'query' => [
                    'taskId' => (string) $taskId,
                ],
            ],
            successCodes: [Response::HTTP_OK, Response::HTTP_NOT_FOUND]
        );

        if (!$response instanceof ResponseInterface) {
            $aristoteApiException = new AristoteApiException(
                'Null response from AristoteApi API'
            );
            $symfonyStyle->error(sprintf('Error while requesting AristoteApi: %s', $aristoteApiException));

            throw $aristoteApiException;
        } elseif (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            $symfonyStyle->warning('There are no transcription jobs that are currently available');

            return Command::SUCCESS;
        }

        try {
            $job = $response->toArray();
        } catch (
            ClientExceptionInterface|
            DecodingExceptionInterface|
            RedirectionExceptionInterface|
            ServerExceptionInterface|
            TransportExceptionInterface $aristoteApiException
        ) {
            throw new AristoteApiException($aristoteApiException->getMessage(), $aristoteApiException->getCode(), $aristoteApiException);
        }

        if (isset($job['enrichmentId'])) {
            $enrichmentId = $job['enrichmentId'];
            if (isset($job['mediaTemporaryUrl'])) {
                $symfonyStyle->info(sprintf('Got 1 job : Enrichment ID => %s', $enrichmentId));
            } else {
                $requestOptions = [
                    'body' => [
                        'status' => 'KO',
                        'failureCause' => 'No MediaUrl in AristoteApi response',
                    ],
                ];

                $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/initial/transcript', $enrichmentId), $requestOptions);

                throw new AristoteApiException('No Enrichment ID or MediaUrl in AristoteApi response');
            }
        } else {
            throw new AristoteApiException('No Enrichment ID in AristoteApi response');
        }

        // Simulate generation initial version
        $symfonyStyle->info('Generating transcript ...');
        sleep(5);

        $requestOptions = [
            'body' => [
                'transcript' => fopen('public/transcript.json', 'r'),
                'taskId' => $taskId,
                'status' => 'OK',
            ],
            'query' => [
                'taskId' => $taskId,
            ],
        ];

        $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/initial/transcript', $enrichmentId), $requestOptions);

        if (200 === $response->getStatusCode()) {
            $symfonyStyle->success(sprintf('Posting intial enrichment version transcript successful ! (Enrichment version ID : %s)', $response->toArray()['id']));

            return Command::SUCCESS;
        } else {
            $symfonyStyle->error(sprintf('Posting AI enrichment failed : %s', $response->toArray()['errors']));

            return Command::FAILURE;
        }
    }
}
