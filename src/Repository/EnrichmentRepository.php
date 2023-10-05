<?php

namespace App\Repository;

use App\Entity\Enrichment;
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

    public function findByCreatedBy(string $client, int $page, int $size, string $sortField, string $sortDirection)
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->where('e.createdBy = :client')
            ->setParameters([
                'client' => $client,
            ]);

        $queryBuilder->orderBy(sprintf('e.%s', $sortField), $sortDirection);

        return $this->paginator->paginate($queryBuilder, $page, $size);
    }
}
