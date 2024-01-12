<?php

namespace App\Repository;

use App\Entity\AnswerPointer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnswerPointer>
 *
 * @method AnswerPointer|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnswerPointer|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnswerPointer[]    findAll()
 * @method AnswerPointer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerPointerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AnswerPointer::class);
    }
}
