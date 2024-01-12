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

#[AsCommand(name: 'app:ai-evaluation-worker', description: 'Generates evaluations')]
class AiEvaluationWorkerCommand extends Command
{
    final public const EVALUATOR = 'ChatGPT';

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
            '/enrichments/job/ai_evaluation/oldest',
            options: [
                'query' => [
                    'taskId' => (string) $taskId,
                    'evaluator' => self::EVALUATOR,
                ],
            ],
            successCodes: [Response::HTTP_OK, Response::HTTP_NOT_FOUND]
        );

        if (!$response instanceof ResponseInterface) {
            $symfonyStyle->error('Error while requesting AristoteApi: Null response from AristoteApi API');

            return Command::FAILURE;
        } elseif (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            $symfonyStyle->warning('There are currently no AI Evaluation jobs available');

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
            $transcriptProvided = isset($job['transcript']);
            $multipleChoiceQuestionsProvided = isset($job['multipleChoiceQuestions']);
            if ($transcriptProvided && $multipleChoiceQuestionsProvided) {
                $multipleChoiceQuestions = $job['multipleChoiceQuestions'];
                $symfonyStyle->info(sprintf('Got 1 job : Enrichment Version ID => %s', $enrichmentVersionId));
            } else {
                $failureCause = !$transcriptProvided && !$multipleChoiceQuestionsProvided ? 'Neither transcript nor MCQs in AristoteApi response' :
                    ($transcriptProvided ? 'No MCQs in AristoteApi response' : 'No transcript in AristoteApi response');
                $requestOptions = [
                    'body' => [
                        'status' => 'KO',
                        'failureCause' => $failureCause,
                    ],
                ];

                $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/%s/ai_evaluation', $enrichmentId, $enrichmentVersionId), $requestOptions);

                throw new AristoteApiException($failureCause);
            }
        } else {
            throw new AristoteApiException('No Enrichment version ID in AristoteApi response');
        }

        // Simulate evaluation
        $symfonyStyle->info('Evaluating AI enrichment ...');

        sleep(1);

        $evaluations = [];
        foreach ($multipleChoiceQuestions as $multipleChoiceQuestion) {
            $evaluations[] = [
                'id' => $multipleChoiceQuestion['id'],
                'evaluation' => json_encode([
                    'criteria1' => true,
                    'criteria2' => false,
                    'criteria3' => true,
                ]),
            ];
        }

        $requestOptions = [
            'body' => json_encode([
                'evaluations' => $evaluations,
                'taskId' => $taskId,
                'status' => 'OK',
            ], JSON_THROW_ON_ERROR),
        ];

        try {
            $response = $this->enrichmentWorkerService->apiRequestWithToken('POST', sprintf('/enrichments/%s/versions/%s/ai_evaluation', $enrichmentId, $enrichmentVersionId), $requestOptions);
        } catch (AristoteApiException $e) {
            $symfonyStyle->error($e->getMessage());

            return Command::FAILURE;
        }

        if (200 === $response->getStatusCode()) {
            $symfonyStyle->info('Posting AI evaluation successful !');

            return Command::SUCCESS;
        } else {
            $symfonyStyle->error(sprintf('Posting AI evaluation failed : %s', $response->toArray()['errors']));

            return Command::FAILURE;
        }
    }
}
