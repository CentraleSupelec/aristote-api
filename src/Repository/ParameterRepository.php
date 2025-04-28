<?php

namespace App\Repository;

use App\Entity\Parameter;
use App\Exception\ParameterNotFoundException;
use App\Exception\ParameterNotNumericException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parameter>
 *
 * @method Parameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parameter[]    findAll()
 * @method Parameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Parameter::class);
    }

    public function findIntegerParameterByName(string $name)
    {
        $parameter = $this->findOneBy(['name' => $name]);

        if ($parameter instanceof Parameter) {
            if (is_numeric($parameter->getValue())) {
                return (float) $parameter->getValue();
            } else {
                throw new ParameterNotNumericException();
            }
        } else {
            throw new ParameterNotFoundException();
        }
    }
}
