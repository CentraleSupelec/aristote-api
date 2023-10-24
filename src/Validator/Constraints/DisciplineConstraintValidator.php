<?php

namespace App\Validator\Constraints;

use App\Entity\EnrichmentVersionMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DisciplineConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DisciplineConstraint) {
            throw new UnexpectedTypeException($constraint, DisciplineConstraint::class);
        }

        if (null === $value) {
            return;
        }
        $enrichmentVersionMetada = $this->context->getObject();

        if (!$enrichmentVersionMetada instanceof EnrichmentVersionMetadata) {
            throw new UnexpectedTypeException($constraint, EnrichmentVersionMetadata::class);
        }

        $disciplines = $enrichmentVersionMetada->getEnrichmentVersion()->getEnrichment()->getDisciplines();

        if (!in_array($value, $disciplines)) {
            $this->context->buildViolation($constraint->notInListOfEnrichmentDisciplines)->addViolation();
        }
    }
}
