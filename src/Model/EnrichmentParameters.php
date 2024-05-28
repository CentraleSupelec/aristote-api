<?php

namespace App\Model;

use App\Constants;
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
    #[Assert\Choice(callback: [Constants::class, 'getEvaluators'], multiple: false, message: 'Invalid aiEvaluation value')]
    private ?string $aiEvaluation = null;

    #[OA\Property(property: 'aiModel', description: 'AI Model to be used for enrichment', type: 'string')]
    private ?string $aiModel = null;

    #[OA\Property(property: 'infrastructure', description: 'Infrastructure to be used for enrichment', type: 'string')]
    private ?string $infrastructure = null;

    #[OA\Property(property: 'language', description: 'Language of the quizzes', type: 'string')]
    private ?string $language = null;

    #[OA\Property(property: 'translateTo', description: 'Translate to this language', type: 'string')]
    private ?string $translateTo = null;

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

    public function getAiModel(): ?string
    {
        return $this->aiModel;
    }

    public function setAiModel(?string $aiModel): self
    {
        $this->aiModel = $aiModel;

        return $this;
    }

    public function getInfrastructure(): ?string
    {
        return $this->infrastructure;
    }

    public function setInfrastructure(?string $infrastructure): self
    {
        $this->infrastructure = $infrastructure;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getTranslateTo(): ?string
    {
        return $this->translateTo;
    }

    public function setTranslateTo(?string $translateTo): self
    {
        $this->translateTo = $translateTo;

        return $this;
    }
}
