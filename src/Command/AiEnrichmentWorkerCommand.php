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
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(name: 'app:ai-enrichment-worker', description: 'Generates enrichment')]
class AiEnrichmentWorkerCommand extends Command
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

        $response = $this->enrichmentWorkerService->apiRequestWithToken('GET', '/enrichments/job/ai_enrichment/oldest', successCodes: [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
        if (!$response instanceof ResponseInterface) {
            $symfonyStyle->error('Error while requesting AristoteApi: Null response from AristoteApi API');

            return Command::FAILURE;
        } elseif (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            $symfonyStyle->warning('There are currently no AI Enrichment jobs available');

            return Command::SUCCESS;
        }

        try {
            $job = $response->toArray();
        } catch (
            ClientExceptionInterface|
            DecodingExceptionInterface|
            RedirectionExceptionInterface|
            ServerExceptionInterface|
            TransportExceptionInterface $exception
        ) {
            throw new AristoteApiException($exception->getMessage(), $exception->getCode(), $exception);
        }
        if (isset($job['enrichmentVersionId']) && isset($job['transcript'])) {
            $enrichmentVersionId = $job['enrichmentVersionId'];
            $disciplines = $job['disciplines'];
            $mediaTypes = $job['mediaTypes'];

            $symfonyStyle->info(sprintf('Got 1 job : Enrichment version ID to fill with AI Enrichment => %s', $enrichmentVersionId));
        } else {
            throw new AristoteApiException('No Enrichment ID or MediaUrl in AristoteApi response');
        }

        // Simulate generation initial version
        $symfonyStyle->info('Generating AI enrichment ...');

        sleep(10);

        $requestOptions = [
            'body' => json_encode([
                'enrichmentVersionMetadata' => [
                    'title' => 'Worker enrichment',
                    'description' => 'This is an example of an enrichment version',
                    'topics' => ['Random topic 1', 'Random topic 2'],
                    'discipline' => $disciplines[0],
                    'mediaType' => $mediaTypes[0],
                ],
                'multipleChoiceQuestions' => [
                    [
                        'question' => 'Question 1',
                        'explanation' => 'Question 1 explanation',
                        'choices' => [
                            [
                                'optionText' => 'Option 1',
                                'correctAnswer' => true,
                            ],
                            [
                                'optionText' => 'Option 2',
                                'correctAnswer' => false,
                            ],
                        ],
                    ],
                    [
                        'question' => 'Question 2',
                        'explanation' => 'Question 2 explanation',
                        'choices' => [
                            [
                                'optionText' => 'Option 1',
                                'correctAnswer' => true,
                            ],
                            [
                                'optionText' => 'Option 2',
                                'correctAnswer' => false,
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        try {
            $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/versions/%s/ai_enrichment', $enrichmentVersionId), $requestOptions);
        } catch (AristoteApiException $e) {
            $symfonyStyle->error($e->getMessage());

            return Command::FAILURE;
        }

        if (200 === $response->getStatusCode()) {
            $symfonyStyle->info('Posting AI enrichment successful !');

            return Command::SUCCESS;
        } else {
            $symfonyStyle->error(sprintf('Posting AI enrichment failed : %s', $response->toArray()['errors']));

            return Command::FAILURE;
        }
    }
}
