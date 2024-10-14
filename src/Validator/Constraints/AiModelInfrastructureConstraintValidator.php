<?php

namespace App\Validator\Constraints;

use App\Entity\AiModel;
use App\Entity\ApiClient;
use App\Entity\Infrastructure;
use App\Model\EnrichmentParameters;
use App\Repository\AiModelRepository;
use App\Repository\ApiClientRepository;
use App\Repository\InfrastructureRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AiModelInfrastructureConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private ApiClientRepository $apiClientRepository,
        private AiModelRepository $aiModelRepository,
        private InfrastructureRepository $infrastructureRepository
    ) {
        $this->apiClientRepository = $apiClientRepository;
        $this->aiModelRepository = $aiModelRepository;
        $this->infrastructureRepository = $infrastructureRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof AiModelInfrastructureConstraint) {
            throw new UnexpectedTypeException($constraint, AiModelInfrastructureConstraint::class);
        }

        if (!$value instanceof EnrichmentParameters) {
            throw new UnexpectedValueException($value, EnrichmentParameters::class);
        }

        if (null === $value->getAiModel() && null === $value->getInfrastructure()) {
            return;
        }
        $aiModel = null;
        $infrastructure = null;

        if (null !== $value->getAiModel()) {
            $aiModel = $this->aiModelRepository->findOneBy(['name' => $value->getAiModel()]);
            if (!$aiModel instanceof AiModel) {
                $this->context->buildViolation($constraint->aiModelNotFound)
                    ->atPath('aiModel')
                    ->addViolation();
            }
        }

        if (null !== $value->getInfrastructure()) {
            $infrastructure = $this->infrastructureRepository->findOneBy(['name' => $value->getInfrastructure()]);
            if (!$infrastructure instanceof Infrastructure) {
                $this->context->buildViolation($constraint->infrastructureNotFound)
                    ->atPath('infrastructure')
                    ->addViolation();
            }
        }

        $apiClient = null;

        if ($aiModel instanceof AiModel && $infrastructure instanceof Infrastructure) {
            $apiClient = $this->apiClientRepository->findOneBy(['aiModel' => $aiModel, 'infrastructure' => $infrastructure]);
        } elseif ($aiModel instanceof AiModel) {
            $apiClient = $this->apiClientRepository->findOneBy(['aiModel' => $aiModel]);
        } elseif ($infrastructure instanceof Infrastructure) {
            $apiClient = $this->apiClientRepository->findOneBy(['infrastructure' => $infrastructure]);
        } else {
            return;
        }

        if (!$apiClient instanceof ApiClient) {
            $this->context->buildViolation($constraint->noEnrichmentClientFound)
                ->addViolation();
        }
    }
}
