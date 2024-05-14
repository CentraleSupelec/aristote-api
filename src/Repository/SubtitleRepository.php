<?php

namespace App\Repository;

use App\Entity\Subtitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subtitle>
 *
 * @method Subtitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subtitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subtitle[]    findAll()
 * @method Subtitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubtitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Subtitle::class);
    }
}
