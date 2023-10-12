<?php

namespace App\Repository;

use App\Entity\EnrichmentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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

    public function findByEnrichmentID(string $enrichmentID, int $page, int $size, string $sortField, string $sortDirection)
    {
        $queryBuilder = $this->createQueryBuilder('ev')
            ->where('ev.enrichment = :enrichmentID')
            ->setParameters([
                'enrichmentID' => $enrichmentID,
            ]);

        $queryBuilder->orderBy(sprintf('ev.%s', $sortField), $sortDirection);

        return $this->paginator->paginate($queryBuilder, $page, $size);
    }
}
