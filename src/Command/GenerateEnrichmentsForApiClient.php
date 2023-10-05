<?php

namespace App\Command;

use App\Service\ApiClientManager;
use App\Service\UserManager;
use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:generate-enrichments', description: 'Generates enrichments for a given Api Client.')]
class GenerateEnrichmentsForApiClient extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserManager $userManager,
        private readonly ValidatorInterface $validator,
        private readonly ApiClientManager $apiClientManager,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Generates enrichments for a given Api Client.')
            ->setDefinition([
                new InputArgument('apiClientIdentifier', InputArgument::REQUIRED, 'The Api Client Identifier'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $apiClientIdentifier = $input->getArgument('apiClientIdentifier');
        $clientEntity = $this->apiClientManager->getClientEntity($apiClientIdentifier);

        if (!$clientEntity instanceof ClientEntityInterface) {
            $symfonyStyle->error(sprintf('No Api Client with identifier %s has been found', $apiClientIdentifier));

            return Command::FAILURE;
        }

        EnrichmentFixturesProvider::getEnrichment($clientEntity, $this->entityManager);

        $symfonyStyle->success(sprintf('Created enrichment for Api Client %s', $apiClientIdentifier));

        return Command::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('apiClientIdentifier')) {
            $question = new Question('Please enter an Api Client identifier:');
            $question->setValidator(function ($apiClientIdentifier) {
                if (empty($apiClientIdentifier)) {
                    throw new Exception('The Api Client identifier can not be empty');
                }

                return $apiClientIdentifier;
            });

            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument('apiClientIdentifier', $answer);
        }
    }
}
