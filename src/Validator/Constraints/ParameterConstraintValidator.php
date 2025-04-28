<?php

namespace App\Validator\Constraints;

use App\Constants;
use App\Entity\Parameter;
use App\Exception\ParameterNotFoundException;
use App\Exception\ParameterNotNumericException;
use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ParameterConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ParameterRepository $parameterRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ParameterConstraint) {
            throw new UnexpectedTypeException($constraint, ParameterConstraint::class);
        }

        if (!$value instanceof Parameter) {
            throw new UnexpectedValueException($value, Parameter::class);
        }

        $parametersNotFound = [];
        $parametersNotNumeric = [];

        $this->entityManager->getConnection()->beginTransaction();
        $this->entityManager->persist($value);
        $this->entityManager->flush();

        foreach (Constants::getMandatoryParameters() as $mandatoryParameter) {
            try {
                $this->parameterRepository->findIntegerParameterByName($mandatoryParameter);
            } catch (ParameterNotFoundException) {
                $parametersNotFound[] = $mandatoryParameter;
            } catch (ParameterNotNumericException) {
                $parametersNotNumeric[] = $mandatoryParameter;
            }
        }

        if ([] !== $parametersNotFound) {
            $this->context->buildViolation($constraint->parameterNotFound)
                ->atPath('name')
                ->setParameter('%parameters%', implode(',', $parametersNotFound))
                ->addViolation();
        }

        if ([] !== $parametersNotNumeric) {
            $this->context->buildViolation($constraint->parameterNotNumeric)
                ->atPath('value')
                ->setParameter('%parameters%', implode(',', $parametersNotNumeric))
                ->addViolation();
        }

        if ([] !== $parametersNotFound || [] !== $parametersNotNumeric) {
            $this->entityManager->getConnection()->rollBack();
        } else {
            $this->entityManager->getConnection()->commit();
        }
    }
}
