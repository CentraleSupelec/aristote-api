<?php

namespace App\Repository;

use App\Entity\MultipleChoiceQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MultipleChoiceQuestion>
 *
 * @method MultipleChoiceQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultipleChoiceQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultipleChoiceQuestion[]    findAll()
 * @method MultipleChoiceQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultipleChoiceQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, MultipleChoiceQuestion::class);
    }
}
