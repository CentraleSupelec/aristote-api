<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class LanguageConstraint extends Constraint
{
    public string $noLanguageForSubtitles = 'validation.translation_constraint.no_language_for_subtitles';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
