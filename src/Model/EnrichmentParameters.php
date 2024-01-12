<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentParameters
{
    #[OA\Property(property: 'mediaTypes', description: 'List of media types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'mediaTypes[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one media type is expected')]
    private array $mediaTypes = [];

    #[OA\Property(property: 'disciplines', description: 'List of disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'disciplines[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected')]
    private array $disciplines = [];

    #[OA\Property(property: 'aiEvaluation', description: 'The name of the AI to evaluate the MCQs', type: 'string')]
    private ?string $aiEvaluation = null;

    public function getMediaTypes(): array
    {
        return $this->mediaTypes;
    }

    public function setMediaTypes(array $mediaTypes): self
    {
        $this->mediaTypes = $mediaTypes;

        return $this;
    }

    public function getDisciplines(): array
    {
        return $this->disciplines;
    }

    public function setDisciplines(array $disciplines): self
    {
        $this->disciplines = $disciplines;

        return $this;
    }

    public function getAiEvaluation(): ?string
    {
        return $this->aiEvaluation;
    }

    public function setAiEvaluation(?string $aiEvaluation): self
    {
        $this->aiEvaluation = $aiEvaluation;

        return $this;
    }
}
