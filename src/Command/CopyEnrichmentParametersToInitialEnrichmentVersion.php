<?php

namespace App\Command;

use App\Entity\EnrichmentVersion;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:copy-enrichment-parameters', description: 'Sets status to failure for enrichments that have reached maximum tries')]
class CopyEnrichmentParametersToInitialEnrichmentVersion extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnrichmentRepository $enrichmentRepository,
        private readonly EnrichmentVersionRepository $enrichmentVersionRepository
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

        $enrichments = $this->enrichmentRepository->findAll();
        $symfonyStyle->info(sprintf('%s enrichments to copy parameters from', count($enrichments)));

        foreach ($enrichments as $enrichment) {
            /* @var Enrichment $enrichment */
            $intiailVersion = $this->enrichmentVersionRepository->findOneBy(['id' => $enrichment->getInitialVersionId()]);
            if ($intiailVersion instanceof EnrichmentVersion) {
                $intiailVersion
                    ->setAiGenerated(true)
                    ->setNotificationWebhookUrl($enrichment->getNotificationWebhookUrl())
                    ->setDisciplines($enrichment->getDisciplines())
                    ->setMediaTypes($enrichment->getMediaTypes())
                    ->setAiEvaluation($enrichment->getAiEvaluation())
                    ->setEndUserIdentifier($enrichment->getEndUserIdentifier())
                    ->setAiModel($enrichment->getAiModel())
                    ->setInfrastructure($enrichment->getInfrastructure())
                    ->setLanguage($enrichment->getLanguage())
                    ->setTranslateTo($enrichment->getTranslateTo())
                ;
            }
            $this->entityManager->flush();
        }
        $symfonyStyle->success(sprintf('Successfully copied parameters form %s enrichments to their initial versions', count($enrichments)));

        return Command::SUCCESS;
    }
}
