<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class AudioFileConstraint extends Constraint
{
    public string $invalidFormat = 'The uploaded file should be an audio';
}
