<?php

namespace App\Validator\Constraints;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use UnexpectedValueException;

class VideoFileConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof VideoFileConstraint) {
            throw new UnexpectedTypeException($constraint, VideoFileConstraint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof File) {
            throw new UnexpectedValueException($value, File::class);
        }

        /** @var File $value */
        if (!str_starts_with($value->getMimeType(), 'video/')) {
            $this->context->buildViolation($constraint->invalidFormat)->addViolation();
        }
    }
}
