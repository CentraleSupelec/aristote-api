<?php

namespace App\Repository;

use App\Entity\AiModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiModel>
 *
 * @method AiModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method AiModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method AiModel[]    findAll()
 * @method AiModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AiModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AiModel::class);
    }
}
