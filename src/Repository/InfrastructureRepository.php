<?php

namespace App\Repository;

use App\Entity\Infrastructure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Infrastructure>
 *
 * @method Infrastructure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Infrastructure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Infrastructure[]    findAll()
 * @method Infrastructure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfrastructureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Infrastructure::class);
    }
}
