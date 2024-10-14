<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class AiModelInfrastructureConstraint extends Constraint
{
    public string $aiModelNotFound = 'validation.ai_model_infrastructure_constraint.ai_model_not_found';
    public string $infrastructureNotFound = 'validation.ai_model_infrastructure_constraint.infrastructure_not_found';
    public string $noEnrichmentClientFound = 'validation.ai_model_infrastructure_constraint.no_enrichment_client_found';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
