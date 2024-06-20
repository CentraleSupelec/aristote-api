<?php

namespace App\Validator\Constraints;

use App\Entity\Enrichment;
use App\Entity\Subtitle;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class LanguageConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof LanguageConstraint) {
            throw new UnexpectedTypeException($constraint, LanguageConstraint::class);
        }

        if (!$value instanceof Enrichment) {
            throw new UnexpectedValueException($value, Enrichment::class);
        }

        if (!$value->getMedia() instanceof Subtitle) {
            return;
        }

        if (null === $value->getLanguage()) {
            $this->context->buildViolation($constraint->noLanguageForSubtitles)
                ->atPath('language')
                ->addViolation();
        }
    }
}
