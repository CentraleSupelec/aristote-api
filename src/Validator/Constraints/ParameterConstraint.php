<?php

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class ParameterConstraint extends Constraint
{
    public string $parameterNotFound = 'validation.parameter_constraint.parameter_not_found';
    public string $parameterNotNumeric = 'validation.parameter_constraint.parameter_not_numeric';

    #[Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
