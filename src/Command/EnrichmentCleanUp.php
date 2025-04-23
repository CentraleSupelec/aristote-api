<?php

namespace App\Command;

use App\Entity\Enrichment;
use App\Repository\EnrichmentRepository;
use App\Utils\EnrichmentUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:enrichment-clean', description: 'Sets status to failure for enrichments that have reached maximum tries')]
class EnrichmentCleanUp extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnrichmentRepository $enrichmentRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer,
        private readonly EnrichmentUtils $enrichmentUtils,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sets status to failure for enrichments that have reached maximum tries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $enrichments = $this->enrichmentRepository->findEnrichmentsWithMaxTriesAtWaitingStatus();
        $symfonyStyle->info(sprintf('%s enrichments to pass to failure status because max retries reached', count($enrichments)));

        foreach ($enrichments as $enrichment) {
            /* @var Enrichment $enrichment */
            $enrichment
                ->setStatus(Enrichment::STATUS_FAILURE)
                ->setFailureCause(sprintf('Max retries reached (%s)', $enrichment->getRetries()))
            ;
            $this->entityManager->flush();
            $this->enrichmentUtils->sendNotification($enrichment);
        }

        $symfonyStyle->success(sprintf('Successfully passed %s enrichments to failure status', count($enrichments)));

        $enrichments = $this->enrichmentRepository->findEnrichmentsInUploadingStatusForMoreThanXMinutes();
        $symfonyStyle->info(sprintf('%s enrichments to pass to failure status because uploading took so long', count($enrichments)));

        foreach ($enrichments as $enrichment) {
            /* @var Enrichment $enrichment */
            $enrichment
                ->setStatus(Enrichment::STATUS_FAILURE)
                ->setFailureCause('Uploading took too long')
            ;
            $this->entityManager->flush();
            $this->enrichmentUtils->sendNotification($enrichment);
        }
        $symfonyStyle->success(sprintf('Successfully passed %s enrichments to failure status', count($enrichments)));

        return Command::SUCCESS;
    }
}
