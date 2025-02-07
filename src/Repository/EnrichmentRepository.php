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
    public function __construct(
        private readonly int $maxRetries,
        private readonly int $transcriptionWorkerTimeoutInMinutes,
        private readonly int $aiEnrichmentWorkerTimeoutInMinutes,
        private readonly int $aiEvaluationWorkerTimeoutInMinutes,
        private readonly int $translationWorkerTimeoutInMinutes,
        ManagerRegistry $managerRegistry,
        private readonly PaginatorInterface $paginator,
    ) {
        parent::__construct($managerRegistry, Enrichment::class);
    }

    public function findByCreatedBy(string $client, int $page, int $size, string $sortField, string $sortDirection, ?string $endUserIdentifier)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.createdBy = :client')
            ->setParameter('client', $client)
        ;

        $qb->andWhere($qb->expr()->eq('e.deleted', ':deleted'))
            ->setParameter('deleted', false)
        ;

        if ($endUserIdentifier) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'e.endUserIdentifier = :endUserIdentifier',
                    $qb->expr()->like('e.contributors', ':endUserIdentifierJson')
                )
            )
                ->setParameter('endUserIdentifier', $endUserIdentifier)
                ->setParameter('endUserIdentifierJson', '%'.$endUserIdentifier.'%');
        }

        $qb->orderBy(sprintf('e.%s', $sortField), $sortDirection);

        return $this->paginator->paginate($qb, $page, $size);
    }

    public function findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes(?string $aiModel = null, ?string $infrastructure = null, bool $treatUnspecifiedModelOrInfrastructure = false): ?Enrichment
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
        ));

        $qb->andWhere($qb->expr()->lt('e.retries', ':maxRetries'));
        $qb->andWhere($qb->expr()->eq('e.deleted', ':deleted'));

        $parameters = [
            'statusWaitingAiEnrichment' => Enrichment::STATUS_WAITING_AI_ENRICHMENT,
            'statusAiEnrichment' => Enrichment::STATUS_AI_ENRICHING,
            'timeThreshold' => (new DateTime())->modify('-'.$this->aiEnrichmentWorkerTimeoutInMinutes.' minutes'),
            'maxRetries' => $this->maxRetries,
            'deleted' => false,
        ];

        if ($aiModel) {
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('e.aiModel', ':aiModel'),
                    $qb->expr()->isNull('e.aiModel'))
                );
            } else {
                $qb->andWhere($qb->expr()->eq('e.aiModel', ':aiModel'));
            }
            $parameters['aiModel'] = $aiModel;
        } else {
            $qb->andWhere($qb->expr()->isNull('e.aiModel'));
        }

        if ($infrastructure) {
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('e.infrastructure', ':infrastructure'),
                    $qb->expr()->isNull('e.infrastructure'))
                );
            } else {
                $qb->andWhere($qb->expr()->eq('e.infrastructure', ':infrastructure'));
            }
            $parameters['infrastructure'] = $infrastructure;
        } else {
            $qb->andWhere($qb->expr()->isNull('e.infrastructure'));
        }

        $qb
            ->setParameters($parameters)
            ->orderBy('e.priority', 'DESC')
            ->addOrderBy('e.latestEnrichmentRequestedAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findOldestEnrichmentInWaitingMediaTranscriptionStatusOrTranscribingMediaStatusForMoreThanXMinutes(
        ?string $model = null,
        ?string $infrastructure = null,
        bool $treatUnspecifiedModelOrInfrastructure = false,
    ): ?Enrichment {
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
            ->andWhere($qb->expr()->lt('e.retries', ':maxRetries'))
            ->andWhere($qb->expr()->eq('e.deleted', ':deleted'));

        $parameters = [
            'statusWaitingMediaTranscription' => Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION,
            'statusTranscribingMedia' => Enrichment::STATUS_TRANSCRIBING_MEDIA,
            'timeThreshold' => (new DateTime())->modify('-'.$this->transcriptionWorkerTimeoutInMinutes.' minutes'),
            'maxRetries' => $this->maxRetries,
            'deleted' => false,
        ];

        $qb->leftJoin('e.createdBy', 'c');

        if ($model) {
            $qb->leftJoin('c.transcriptionModel', 'm');
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->eq('m.name', ':model'),
                        $qb->expr()->isNull('c.transcriptionModel'))
                    );
            } else {
                $qb->andWhere($qb->expr()->eq('m.name', ':model'));
            }
            $parameters['model'] = $model;
        } else {
            $qb->andWhere($qb->expr()->isNull('c.transcriptionModel'));
        }

        if ($infrastructure) {
            $qb->leftJoin('c.transcriptionInfrastructure', 'i');
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('i.name', ':infrastructure'),
                    $qb->expr()->isNull('c.transcriptionInfrastructure'))
                );
            } else {
                $qb->andWhere($qb->expr()->eq('i.name', ':infrastructure'));
            }
            $parameters['infrastructure'] = $infrastructure;
        } else {
            $qb->andWhere($qb->expr()->isNull('c.transcriptionInfrastructure'));
        }

        $qb
            ->setParameters($parameters)
            ->orderBy('e.priority', 'DESC')
            ->addOrderBy('e.latestEnrichmentRequestedAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findOldestEnrichmentInWaitingAiEvaluationStatusOrAiEvaluatingStatusForMoreThanXMinutesByEvaluator(string $evaluator): ?Enrichment
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
            ->andWhere($qb->expr()->lt('e.retries', ':maxRetries'))
            ->andWhere($qb->expr()->eq('e.deleted', ':deleted'))
            ->setParameters([
                'statusWaitingAiEvaluation' => Enrichment::STATUS_WAITING_AI_EVALUATION,
                'statusAiEvaluating' => Enrichment::STATUS_AI_EVALUATING,
                'timeThreshold' => (new DateTime())->modify('-'.$this->aiEvaluationWorkerTimeoutInMinutes.' minutes'),
                'evaluator' => $evaluator,
                'maxRetries' => $this->maxRetries,
                'deleted' => false,
            ])
            ->orderBy('e.priority', 'DESC')
            ->addOrderBy('e.latestEnrichmentRequestedAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findOldestEnrichmentInWaitingTranslationStatusOrTranslatingStatusForMoreThanXMinutes(
        ?string $model = null,
        ?string $infrastructure = null,
        bool $treatUnspecifiedModelOrInfrastructure = false,
    ): ?Enrichment {
        $qb = $this->createQueryBuilder('e');
        $qb->where($qb->expr()->orX(
            $qb->expr()->eq('e.status', ':statusWaitingTranslation'),
            $qb->expr()->andX(
                $qb->expr()->eq('e.status', ':statusTranslating'),
                $qb->expr()->lte(
                    'e.translationStartedAt',
                    ':timeThreshold'
                )
            )
        ))
            ->andWhere($qb->expr()->lt('e.retries', ':maxRetries'))
            ->andWhere($qb->expr()->eq('e.deleted', ':deleted'));

        $parameters = [
            'statusWaitingTranslation' => Enrichment::STATUS_WAITING_TRANSLATION,
            'statusTranslating' => Enrichment::STATUS_TRANSLATING,
            'timeThreshold' => (new DateTime())->modify('-'.$this->translationWorkerTimeoutInMinutes.' minutes'),
            'maxRetries' => $this->maxRetries,
            'deleted' => false,
        ];

        $qb->leftJoin('e.createdBy', 'c');

        if ($model) {
            $qb->leftJoin('c.translationModel', 'm');
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->eq('m.name', ':model'),
                        $qb->expr()->isNull('c.translationModel'))
                    );
            } else {
                $qb->andWhere($qb->expr()->eq('m.name', ':model'));
            }
            $parameters['model'] = $model;
        } else {
            $qb->andWhere($qb->expr()->isNull('c.translationModel'));
        }

        if ($infrastructure) {
            $qb->leftJoin('c.translationInfrastructure', 'i');
            if ($treatUnspecifiedModelOrInfrastructure) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('i.name', ':infrastructure'),
                    $qb->expr()->isNull('c.translationInfrastructure'))
                );
            } else {
                $qb->andWhere($qb->expr()->eq('i.name', ':infrastructure'));
            }
            $parameters['infrastructure'] = $infrastructure;
        } else {
            $qb->andWhere($qb->expr()->isNull('c.translationInfrastructure'));
        }

        $qb
            ->setParameters($parameters)
            ->orderBy('e.priority', 'DESC')
            ->addOrderBy('e.latestEnrichmentRequestedAt', 'ASC')
        ;

        $enrichments = $qb->getQuery()->getResult();

        if ([] !== $enrichments) {
            return $enrichments[0];
        }

        return null;
    }

    public function findEnrichmentsWithMaxTriesAtWaitingStatus(): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb
            ->where($qb->expr()->gte('e.retries', ':maxRetries'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('e.status', ':statusTranscribingMedia'),
                        $qb->expr()->eq('e.status', ':statusWaitingMediaTranscription'),
                    ),
                    $qb->expr()->lte(
                        'e.transribingStartedAt',
                        ':transcriptionThreshold'
                    )
                ),
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('e.status', ':statusAiEnrichment'),
                        $qb->expr()->eq('e.status', ':statusWaitingAiEnrichment'),
                    ),
                    $qb->expr()->lte(
                        'e.aiEnrichmentStartedAt',
                        ':aiEnrichmentThreshold'
                    )
                ),
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('e.status', ':statusAiEvaluating'),
                        $qb->expr()->eq('e.status', ':statusWaitingAiEvaluation'),
                    ),
                    $qb->expr()->lte(
                        'e.aiEvaluationStartedAt',
                        ':aiEvaluationThreshold'
                    )
                ),
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('e.status', ':statusTranslating'),
                        $qb->expr()->eq('e.status', ':statusWaitingTranslation'),
                    ),
                    $qb->expr()->lte(
                        'e.translationStartedAt',
                        ':translationThreshold'
                    )
                ),
            ))
            ->andWhere($qb->expr()->eq('e.deleted', ':deleted'))
            ->setParameters([
                'maxRetries' => $this->maxRetries,
                'statusTranscribingMedia' => Enrichment::STATUS_TRANSCRIBING_MEDIA,
                'statusAiEnrichment' => Enrichment::STATUS_AI_ENRICHING,
                'statusAiEvaluating' => Enrichment::STATUS_AI_EVALUATING,
                'statusTranslating' => Enrichment::STATUS_TRANSLATING,
                'statusWaitingMediaTranscription' => Enrichment::STATUS_WAITING_MEDIA_TRANSCRIPTION,
                'statusWaitingAiEnrichment' => Enrichment::STATUS_WAITING_AI_ENRICHMENT,
                'statusWaitingAiEvaluation' => Enrichment::STATUS_WAITING_AI_EVALUATION,
                'statusWaitingTranslation' => Enrichment::STATUS_WAITING_TRANSLATION,
                'transcriptionThreshold' => (new DateTime())->modify('-'.$this->transcriptionWorkerTimeoutInMinutes.' minutes'),
                'aiEnrichmentThreshold' => (new DateTime())->modify('-'.$this->aiEnrichmentWorkerTimeoutInMinutes.' minutes'),
                'aiEvaluationThreshold' => (new DateTime())->modify('-'.$this->aiEvaluationWorkerTimeoutInMinutes.' minutes'),
                'translationThreshold' => (new DateTime())->modify('-'.$this->translationWorkerTimeoutInMinutes.' minutes'),
                'deleted' => false,
            ]);

        return $qb->getQuery()->getResult();
    }

    public function findEnrichmentsInUploadingStatusForMoreThanXMinutes(): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb
            ->where($qb->expr()->eq('e.status', ':statusUploadingMedia'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.uploadStartedAt'),
                    $qb->expr()->lte(
                        'e.uploadStartedAt',
                        ':uploadThreshold'
                    )
                )
            )
            ->andWhere($qb->expr()->eq('e.deleted', ':deleted'))
            ->setParameters([
                'statusUploadingMedia' => Enrichment::STATUS_UPLOADING_MEDIA,
                'uploadThreshold' => (new DateTime())->modify('-60 minutes'),
                'deleted' => false,
            ]);

        return $qb->getQuery()->getResult();
    }
}
