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

#[AsCommand(name: 'app:translation-worker', description: 'Translates an enrichment version')]
class TranslationWorkerCommand extends Command
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
            '/enrichments/job/translation/oldest',
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
            $symfonyStyle->warning('There are currently no translation jobs available');

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

        if (isset($job['enrichmentVersionId']) && isset($job['enrichmentId'])) {
            $enrichmentVersionId = $job['enrichmentVersionId'];
            $enrichmentId = $job['enrichmentId'];
            $enrichmentVersionMetadata = $job['enrichmentVersionMetadata'];
            $multipleChoiceQuestions = $job['multipleChoiceQuestions'];
            $translateTo = $job['translateTo'];
            $symfonyStyle->info(sprintf('Got 1 job : Enrichment Version ID => %s', $enrichmentVersionId));
        } else {
            throw new AristoteApiException('Enrichment ID or Enrichment version ID in AristoteApi response');
        }

        // Simulate translating enrichment version
        $symfonyStyle->info('Translating enrichment version ...');

        sleep(1);

        $body = [
            'enrichmentVersionMetadata' => [
                'title' => $this->translate($enrichmentVersionMetadata['title'], $translateTo),
                'description' => $this->translate($enrichmentVersionMetadata['description'], $translateTo),
                'topics' => array_map(fn (string $topic) => $this->translate($topic, $translateTo), $enrichmentVersionMetadata['topics']),
            ],
            'multipleChoiceQuestions' => array_map(fn (array $mcq) => [
                    'id' => $mcq['id'],
                    'question' => $this->translate($mcq['question'], $translateTo),
                    'explanation' => $this->translate($mcq['explanation'], $translateTo),
                    'choices' => array_map(
                        fn (array $choice) => [
                            'id' => $choice['id'],
                            'optionText' => $this->translate($choice['optionText'], $translateTo),
                        ], $mcq['choices']
                    ),
                ], $multipleChoiceQuestions
            ),
            'taskId' => $taskId,
            'status' => 'OK',
        ];
        dump($body);
        $requestOptions = [
            'body' =>
                // $body
                json_encode($body, JSON_PRETTY_PRINT),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        try {
            $response = $this->enrichmentWorkerService->apiRequestWithToken(
                'POST', sprintf('/enrichments/%s/versions/%s/translation', $enrichmentId, $enrichmentVersionId), $requestOptions
            );
        } catch (AristoteApiException $e) {
            $symfonyStyle->error($e->getMessage());

            return Command::FAILURE;
        }

        if (200 === $response->getStatusCode()) {
            $symfonyStyle->info('Posting translation successful !');

            return Command::SUCCESS;
        } else {
            $symfonyStyle->error(sprintf('Posting translation failed : %s', $response->toArray()['errors']));

            return Command::FAILURE;
        }
    }

    private function translate(string $text, string $translateTo): string
    {
        return $translateTo.' _ '.$text;
    }
}
