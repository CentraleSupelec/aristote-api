<?php

namespace App\Command;

use App\Entity\Enrichment;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:fill-intial-and-last-versions-enrichment', description: 'Sets status to failure for enrichments that have reached maximum tries', aliases: ['app:filev'])]
class FillInitialAndLastEnrichmentVersion extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnrichmentRepository $enrichmentRepository,
        private readonly EnrichmentVersionRepository $enrichmentVersionRepository,
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
        $symfonyStyle->info(sprintf('%s enrichments to fill initial and last', count($enrichments)));

        foreach ($enrichments as $enrichment) {
            $enrichmentVersionsAsc = $this->enrichmentVersionRepository->findByEnrichmentId($enrichment->getId(), 1, 1, 'createdAt', 'ASC');
            if ($enrichmentVersionsAsc->getTotalItemCount() > 0) {
                $enrichment->setInitialEnrichmentVersion($enrichmentVersionsAsc->offsetGet(0));
            }

            $enrichmentVersionsDesc = $this->enrichmentVersionRepository->findByEnrichmentId($enrichment->getId(), 1, 1, 'createdAt', 'DESC');
            if ($enrichmentVersionsDesc->getTotalItemCount() > 0) {
                $latestEnrichmentVersion = $enrichmentVersionsDesc->offsetGet(0);

                $latestEnrichmentVersion
                    ->setTranscribedBy($enrichment->getTranscribedBy())
                    ->setAiProcessedBy($enrichment->getAiProcessedBy())
                    ->setTranslatedBy($enrichment->getTranslatedBy())
                    ->setAiEvaluatedBy($enrichment->getAiEvaluatedBy())
                    ->setTranscribingStartedAt($enrichment->getTranscribingStartedAt())
                    ->setTranscribingEndedAt($enrichment->getTranscribingEndedAt())
                    ->setAiEnrichmentStartedAt($enrichment->getAiEnrichmentStartedAt())
                    ->setAiEnrichmentEndedAt($enrichment->getAiEnrichmentEndedAt())
                    ->setTranslationStartedAt($enrichment->getTranslationStartedAt())
                    ->setTranslationEndedAt($enrichment->getTranslationEndedAt())
                    ->setAiEvaluationStartedAt($enrichment->getAiEvaluationStartedAt())
                    ->setAiEvaluationEndedAt($enrichment->getAiEvaluationEndedAt())
                ;

                if (Enrichment::STATUS_FAILURE == $enrichment->getStatus()) {
                    $latestEnrichmentVersion->setFailureCause($enrichment->getFailureCause());
                }

                $enrichment->setLastEnrichmentVersion($latestEnrichmentVersion);
            }
            $this->entityManager->flush();
        }

        $symfonyStyle->success(sprintf('Successfully filled initial and last enrichment version for %s enrichments', count($enrichments)));

        return Command::SUCCESS;
    }
}
