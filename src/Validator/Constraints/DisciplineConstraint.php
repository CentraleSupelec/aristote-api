<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class DisciplineConstraint extends Constraint
{
    public string $notInListOfEnrichmentDisciplines = 'The discipline is not in the list of enrichment disciplines';
}
