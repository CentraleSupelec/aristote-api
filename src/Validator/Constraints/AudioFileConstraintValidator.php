<?php

namespace App\Validator\Constraints;

use App\Utils\MimeTypeUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use UnexpectedValueException;

class AudioFileConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly MimeTypeUtils $mimeTypeUtils,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AudioFileConstraint) {
            throw new UnexpectedTypeException($constraint, AudioFileConstraint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof UploadedFile) {
            throw new UnexpectedValueException($value, UploadedFile::class);
        }

        $testing = 'test' === $_ENV['APP_ENV'];
        $mimeType = $testing ? $value->getClientMimeType() : $value->getMimeType();

        if (!$this->mimeTypeUtils->isAudio($mimeType)) {
            $this->context->buildViolation($constraint->invalidFormat)->addViolation();
        }
    }
}
