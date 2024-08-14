<?php

namespace App\Model;

use App\Constants;
use App\Entity\Enrichment;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentParameters
{
    #[OA\Property(property: 'mediaTypes', description: 'List of media types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'mediaTypes[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one media type is expected', groups: ['metadata'])]
    #[Groups(['Default'])]
    private array $mediaTypes = [];

    #[OA\Property(property: 'disciplines', description: 'List of disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'disciplines[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected', groups: ['metadata'])]
    #[Groups(['Default'])]
    private array $disciplines = [];

    #[OA\Property(property: 'aiEvaluation', description: 'The name of the AI to evaluate the MCQs', type: 'string')]
    #[Assert\Choice(callback: [Constants::class, 'getEvaluators'], multiple: false, message: 'Invalid aiEvaluation value')]
    #[Groups(['Default'])]
    private ?string $aiEvaluation = null;

    #[OA\Property(property: 'aiModel', description: 'AI Model to be used for enrichment', type: 'string')]
    #[Groups(['Default'])]
    private ?string $aiModel = null;

    #[OA\Property(property: 'infrastructure', description: 'Infrastructure to be used for enrichment', type: 'string')]
    #[Groups(['Default'])]
    private ?string $infrastructure = null;

    #[OA\Property(property: 'language', description: 'Language of the quizzes', type: 'string')]
    #[Assert\Choice(callback: [Enrichment::class, 'getSupportedLanguages'], multiple: false, message: "The chosen language is not supported. Supported languages are : ['fr', 'en']")]
    #[Groups(['Default'])]
    private ?string $language = null;

    #[OA\Property(property: 'translateTo', description: 'Translate to this language', type: 'string')]
    #[Assert\Choice(callback: [Enrichment::class, 'getSupportedLanguages'], multiple: false, message: "The chosen language to translate to is not supported. Supported languages are : ['fr', 'en']")]
    #[Groups(['Default'])]
    private ?string $translateTo = null;

    #[OA\Property(property: 'generateMetadata', description: 'Request metadata generation', type: 'boolean')]
    #[Groups(['treatments'])]
    private bool $generateMetadata = false;

    #[OA\Property(property: 'generateQuiz', description: 'Request quiz generation', type: 'boolean')]
    #[Groups(['treatments'])]
    private bool $generateQuiz = false;

    #[OA\Property(property: 'generateNotes', description: 'Request notes generation', type: 'boolean')]
    #[Groups(['treatments'])]
    private bool $generateNotes = false;

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

    public function getGenerateMetadata(): bool
    {
        return $this->generateMetadata;
    }

    public function setGenerateMetadata(bool $generateMetadata): self
    {
        $this->generateMetadata = $generateMetadata;

        return $this;
    }

    public function getGenerateQuiz(): bool
    {
        return $this->generateQuiz;
    }

    public function setGenerateQuiz(bool $generateQuiz): self
    {
        $this->generateQuiz = $generateQuiz;

        return $this;
    }

    public function getGenerateNotes(): bool
    {
        return $this->generateNotes;
    }

    public function setGenerateNotes(bool $generateNotes): self
    {
        $this->generateNotes = $generateNotes;

        return $this;
    }
}
