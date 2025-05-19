<?php

namespace App\Validator\Constraints;

use App\Utils\MimeTypeUtils;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use UnexpectedValueException;

class SubtitleFileConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly MimeTypeUtils $mimeTypeUtils,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SubtitleFileConstraint) {
            throw new UnexpectedTypeException($constraint, SubtitleFileConstraint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof File) {
            throw new UnexpectedValueException($value, File::class);
        }

        /** @var File $value */
        if (!$this->mimeTypeUtils->isSubtitleFile($value->getMimeType())) {
            $this->context->buildViolation($constraint->invalidFormat)->addViolation();
        }
    }
}
