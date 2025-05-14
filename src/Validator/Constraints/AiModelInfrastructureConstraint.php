<?php

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class AiModelInfrastructureConstraint extends Constraint
{
    public string $aiModelNotFound = 'validation.ai_model_infrastructure_constraint.ai_model_not_found';
    public string $aiModelNotAuthorized = 'validation.ai_model_infrastructure_constraint.ai_model_not_authorized';
    public string $infrastructureNotFound = 'validation.ai_model_infrastructure_constraint.infrastructure_not_found';
    public string $infrastructureNotAuthorized = 'validation.ai_model_infrastructure_constraint.infrastructure_not_authorized';
    public string $noEnrichmentClientFound = 'validation.ai_model_infrastructure_constraint.no_enrichment_client_found';

    #[Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
