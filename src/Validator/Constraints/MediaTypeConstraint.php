<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class MediaTypeConstraint extends Constraint
{
    public string $notInListOfEnrichmentMediaTypes = 'The media type is not in the list of enrichment media types';
}
