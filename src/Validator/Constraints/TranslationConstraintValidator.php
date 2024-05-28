<?php

namespace App\Validator\Constraints;

use App\Entity\Enrichment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TranslationConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TranslationConstraint) {
            throw new UnexpectedTypeException($constraint, TranslationConstraint::class);
        }

        if (!$value instanceof Enrichment) {
            throw new UnexpectedValueException($value, Enrichment::class);
        }

        if (null === $value->getLanguage()) {
            return;
        }

        if (null === $value->getTranslateTo()) {
            return;
        }

        if ($value->getLanguage() === $value->getTranslateTo()) {
            $this->context->buildViolation($constraint->translateToEqualsLanguage)
                ->atPath('translateTo')
                ->addViolation();
        }
    }
}
