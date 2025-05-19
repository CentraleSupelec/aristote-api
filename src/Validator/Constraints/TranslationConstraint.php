<?php

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class TranslationConstraint extends Constraint
{
    public string $translateToEqualsLanguage = 'validation.translation_constraint.translate_to_equals_language';

    #[Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
