<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema()]
class Sentence
{
    #[OA\Property(property: 'is_transient', type: 'boolean')]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job'])]
    private ?bool $transient = null;

    #[OA\Property(property: 'no_speech_prob', type: 'integer')]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job'])]
    private ?int $noSpeechProb = null;

    #[OA\Property(property: 'start', type: 'integer')]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'translation_job', 'translation_post'])]
    private ?int $start = null;

    #[OA\Property(property: 'end', type: 'integer')]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'translation_job', 'translation_post'])]
    private ?int $end = null;

    #[OA\Property(property: 'text', type: 'string')]
    #[Groups(groups: ['enrichment_versions', 'enrichment_job', 'translation_job', 'translation_post'])]
    private ?string $text = null;

    public function isTransient(): ?bool
    {
        return $this->transient;
    }

    public function setTransient(?bool $transient): self
    {
        $this->transient = $transient;

        return $this;
    }

    public function getNoSpeechProb(): ?int
    {
        return $this->noSpeechProb;
    }

    public function setNoSpeechProb(?int $noSpeechProb): self
    {
        $this->noSpeechProb = $noSpeechProb;

        return $this;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function setStart(?int $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?int
    {
        return $this->end;
    }

    public function setEnd(?int $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }
}
