<?php

namespace App\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use UnexpectedValueException;

class TranscriptFileConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TranscriptFileConstraint) {
            throw new UnexpectedTypeException($constraint, TranscriptFileConstraint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof File) {
            throw new UnexpectedValueException($value, File::class);
        }

        if ('application/json' !== $value->getMimeType()) {
            $this->context->buildViolation($constraint->invalidFormat)->addViolation();
        }
    }
}
