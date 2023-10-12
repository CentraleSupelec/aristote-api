<?php

namespace App\Repository;

use App\Entity\EnrichmentVersionMetadata;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnrichmentVersionMetadata>
 *
 * @method EnrichmentVersionMetadata|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnrichmentVersionMetadata|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnrichmentVersionMetadata[]    findAll()
 * @method EnrichmentVersionMetadata[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnrichmentVersionMetadataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, EnrichmentVersionMetadata::class);
    }
}
