<?php

namespace App\Repository;

use App\Entity\EnrichmentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<EnrichmentVersion>
 *
 * @method EnrichmentVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnrichmentVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnrichmentVersion[]    findAll()
 * @method EnrichmentVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnrichmentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($managerRegistry, EnrichmentVersion::class);
    }

    public function findByEnrichmentId(string $enrichmentId, int $page, int $size, string $sortField, string $sortDirection): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('ev')
            ->where('ev.enrichment = :enrichmentId')
            ->setParameters([
                'enrichmentId' => $enrichmentId,
            ]);

        $queryBuilder->orderBy(sprintf('ev.%s', $sortField), $sortDirection);

        return $this->paginator->paginate($queryBuilder, $page, $size);
    }

    public function findLatestVersionByEnrichmentId(string $enrichmentId): ?EnrichmentVersion
    {
        $enrichmentVersions = new ArrayCollection($this->createQueryBuilder('ev')
            ->where('ev.enrichment = :enrichmentId')
            ->setParameters([
                'enrichmentId' => $enrichmentId,
            ])
            ->orderBy('ev.createdAt', 'desc')
            ->getQuery()
            ->getResult())
        ;

        if (0 !== $enrichmentVersions->count()) {
            return $enrichmentVersions->get(0);
        }

        return null;
    }
}
