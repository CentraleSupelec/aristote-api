<?php

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class LanguageConstraint extends Constraint
{
    public string $noLanguageForSubtitles = 'validation.translation_constraint.no_language_for_subtitles';

    #[Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
