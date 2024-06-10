<?php

namespace App\Utils;

use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\MultipleChoiceQuestion;
use App\Repository\EnrichmentVersionRepository;

class EnrichmentUtils
{
    public function __construct(
        private readonly EnrichmentVersionRepository $enrichmentVersionRepository
    ) {
    }

    public function calculateEvaluationMark(Enrichment $enrichment): ?float
    {
        $latestAiVersion = $this->enrichmentVersionRepository->findLatestAiVersionByEnrichmentId($enrichment->getId());

        if (!$latestAiVersion instanceof EnrichmentVersion) {
            return null;
        }

        $totalThumbs = 0;
        $totalThubmsUp = 0;
        $mcqs = $latestAiVersion->getMultipleChoiceQuestions();
        foreach ($mcqs as $mcq) {
            /** @var MultipleChoiceQuestion $mcq */
            if (null !== $mcq->getThumbUp()) {
                ++$totalThumbs;
                $totalThubmsUp += $mcq->getThumbUp() ? 1 : 0;
            }
        }

        return 0 !== $totalThumbs ? $totalThubmsUp / $totalThumbs : null;
    }
}
