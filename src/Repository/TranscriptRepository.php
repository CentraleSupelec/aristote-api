<?php

namespace App\Repository;

use App\Entity\Transcript;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transcript>
 *
 * @method Transcript|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transcript|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transcript[]    findAll()
 * @method Transcript[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranscriptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Transcript::class);
    }
}
