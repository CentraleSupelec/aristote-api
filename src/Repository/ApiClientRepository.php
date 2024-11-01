<?php

namespace App\Repository;

use App\Entity\ApiClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiClient>
 *
 * @method ApiClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiClient[]    findAll()
 * @method ApiClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ApiClient::class);
    }

    public function getDistinctCombinations()
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->leftJoin('a.aiModel', 'ai')
            ->leftJoin('a.infrastructure', 'i')
            ->where($qb->expr()->orX($qb->expr()->isNotNull('ai.name'), $qb->expr()->isNotNull('i.name')))
            ->groupBy('ai.name', 'i.name')
            ->select(
                'ai.name AS aiModel',
                'i.name AS infrastructure'
            )
        ;

        return $qb->getQuery()->getArrayResult();
    }
}
