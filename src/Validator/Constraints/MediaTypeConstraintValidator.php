<?php

namespace App\Validator\Constraints;

use App\Entity\EnrichmentVersionMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MediaTypeConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MediaTypeConstraint) {
            throw new UnexpectedTypeException($constraint, MediaTypeConstraint::class);
        }

        if (null === $value) {
            return;
        }

        $enrichmentVersionMetada = $this->context->getObject();

        if (!$enrichmentVersionMetada instanceof EnrichmentVersionMetadata) {
            throw new UnexpectedTypeException($constraint, EnrichmentVersionMetadata::class);
        }

        $mediaTypes = $enrichmentVersionMetada->getEnrichmentVersion()->getEnrichment()->getMediaTypes();

        if (!in_array($value, $mediaTypes)) {
            $this->context->buildViolation($constraint->notInListOfEnrichmentMediaTypes)->addViolation();
        }
    }
}
