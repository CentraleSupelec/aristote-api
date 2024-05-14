<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class SubtitleFileConstraint extends Constraint
{
    public string $invalidFormat = 'The uploaded file should be a subtitle';
}
