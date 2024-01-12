<?php

namespace App\Repository;

use App\Entity\Enrichment;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Enrichment>
 *
 * @method Enrichment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Enrichment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Enrichment[]    findAll()
 * @method Enrichment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnrichmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($managerRegistry, Enrichment::class);
    }

    public function findByCreatedBy(string $client, int $page, int $size, string $sortField, string $sortDirection, ?string $endUserIdentifier)
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->where('e.createdBy = :client')
            ->setParameter('client', $client);

        if ($endUserIdentifier) {
            $queryBuilder->andWhere('e.endUserIdentifier = :endUserIdentifier')
                ->setParameter('endUserIdentifier', $endUserIdentifier);
        }

        $queryBuilder->orderBy(sprintf('e.%s', $sortField), $sortDirection);

        return $this->paginator->paginate($queryBuilder, $page, $size);
    }

    public function findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes(int $minutes = 120): ?Enrichment
    {
        $qb = $this->createQueryBuilder('e');
        $qb->where($qb->expr()->orX(
            $qb->expr()->eq('e.status', ':statusWaitingAiEnrichment'),
            $qb->expr()->andX(
                $qb->expr()->eq('e.status', ':statusAiEnrichment'),
                $qb->expr()->lte(
                    'e.aiEnrichmentStartedAt',
                    ':timeThreshold'
                )
            )
        ))
            ->setParameters([
            'statusWaitingAiEnrichment' => Enrichment::STATUS_WAITING_AI_ENRICHMENT,
            'statusAiEnrichment' => Enrichment::STATUS_AI_ENRICHING,
            'timeThreshold' => (new DateTime())->modify('-'.$minutes.' minutes'),
        ])
            ->orderBy('e.createdAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findOldestEnrichmentInWaitingMediaTranscriptionStatusOrTranscribingMediaStatusForMoreThanXMinutes(int $minutes = 120): ?Enrichment
    {
        $qb = $this->createQueryBuilder('e');
        $qb->where($qb->expr()->orX(
            $qb->expr()->eq('e.status', ':statusWaitingMediaTranscription'),
            $qb->expr()->andX(
                $qb->expr()->eq('e.status', ':statusTranscribingMedia'),
                $qb->expr()->lte(
                    'e.transribingStartedAt',
                    ':timeThreshold'
                )
            )
        ))
            ->setParameters([
            'statusWaitingMediaTranscription' => Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION,
            'statusTranscribingMedia' => Enrichment::STATUS_TRANSCRIBING_MEDIA,
            'timeThreshold' => (new DateTime())->modify('-'.$minutes.' minutes'),
        ])
            ->orderBy('e.createdAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findOldestEnrichmentInWaitingAiEvaluationStatusOrAiEvaluatingStatusForMoreThanXMinutesByEvaluator(string $evaluator, int $minutes = 120): ?Enrichment
    {
        $qb = $this->createQueryBuilder('e');

        $qb
            ->where($qb->expr()->eq('e.aiEvaluation', ':evaluator'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('e.status', ':statusWaitingAiEvaluation'),
                $qb->expr()->andX(
                    $qb->expr()->eq('e.status', ':statusAiEvaluating'),
                    $qb->expr()->lte(
                        'e.aiEvaluationStartedAt',
                        ':timeThreshold'
                    )
                ))
            )
            ->setParameters([
                'statusWaitingAiEvaluation' => Enrichment::STATUS_WAITING_AI_EVALUATION,
                'statusAiEvaluating' => Enrichment::STATUS_AI_EVALUATING,
                'timeThreshold' => (new DateTime())->modify('-'.$minutes.' minutes'),
                'evaluator' => $evaluator,
            ])
            ->orderBy('e.createdAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }
}
