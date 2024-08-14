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
        $taskId = Uuid::v7();
        $response = $this->enrichmentWorkerService->apiRequestWithToken(
            'GET',
            '/enrichments/job/ai_enrichment/oldest',
            options: [
                'query' => [
                    'taskId' => (string) $taskId,
                ],
            ],
            successCodes: [Response::HTTP_OK, Response::HTTP_NOT_FOUND]
        );

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

        if (isset($job['enrichmentVersionId'])) {
            $enrichmentVersionId = $job['enrichmentVersionId'];
            $enrichmentId = $job['enrichmentId'];
            if (isset($job['transcript'])) {
                $disciplines = $job['disciplines'];
                $mediaTypes = $job['mediaTypes'];
                $generateMetadata = $job['generateMetadata'];
                $generateQuiz = $job['generateQuiz'];
                $generateNotes = $job['$generateNotes'];
                $symfonyStyle->info(sprintf('Got 1 job : Enrichment Version ID => %s', $enrichmentVersionId));
            } else {
                $requestOptions = [
                    'body' => [
                        'status' => 'KO',
                        'failureCause' => 'No transcript in AristoteApi response',
                    ],
                ];

                $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/%s/ai_enrichment', $enrichmentId, $enrichmentVersionId), $requestOptions);

                throw new AristoteApiException('No transcript in AristoteApi response');
            }
        } else {
            throw new AristoteApiException('No Enrichment version ID in AristoteApi response');
        }

        // Simulate generation initial version
        $symfonyStyle->info('Generating AI enrichment ...');

        sleep(1);

        $generated = [
            'taskId' => $taskId,
            'status' => 'OK',
        ];

        if ($generateMetadata) {
            $generated['enrichmentVersionMetadata'] = [
                'title' => 'Worker enrichment',
                'description' => 'This is an example of an enrichment version',
                'topics' => ['Random topic 1', 'Random topic 2'],
                'discipline' => $disciplines[0],
                'mediaType' => $mediaTypes[0],
            ];
        }

        if ($generateQuiz) {
            $generated['multipleChoiceQuestions'] = [
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
                        [
                            'optionText' => 'Option 3',
                            'correctAnswer' => false,
                        ],
                        [
                            'optionText' => 'Option 4',
                            'correctAnswer' => false,
                        ],
                    ],
                    'answerPointer' => [
                        'startAnswerPointer' => '00:01:30',
                        'stopAnswerPointer' => '00:01:37',
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
                        [
                            'optionText' => 'Option 3',
                            'correctAnswer' => false,
                        ],
                        [
                            'optionText' => 'Option 4',
                            'correctAnswer' => false,
                        ],
                    ],
                    'answerPointer' => [
                        'startAnswerPointer' => '00:02:34',
                        'stopAnswerPointer' => '00:02:43',
                    ],
                ],
            ];
        }

        if ($generateNotes) {
            $generated['notes'] = 'This reunion lasted 3 hours. The main themes discussed are : ...';
        }

        $requestOptions = [
            'body' => json_encode($generated),
        ];

        try {
            $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/%s/ai_enrichment', $enrichmentId, $enrichmentVersionId), $requestOptions);
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
