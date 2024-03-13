<?php

namespace App\Command;

use App\Entity\Enrichment;
use App\Repository\EnrichmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:enrichment-clean', description: 'Sets status to failure for enrichments that have reached maximum tries')]
class EnrichmentCleanUp extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnrichmentRepository $enrichmentRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sets status to failure for enrichments that have reached maximum tries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new SymfonyStyle($input, $output);

        $enrichments = $this->enrichmentRepository->findEnrichmentsWithMaxTriesNotAtWaitingStatus();

        foreach ($enrichments as $enrichment) {
            /* @var Enrichment $enrichment */
            $enrichment
                ->setStatus(Enrichment::STATUS_FAILURE)
                ->setFailureCause(sprintf('Max retries reached (%s)', $enrichment->getRetries()))
            ;
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
