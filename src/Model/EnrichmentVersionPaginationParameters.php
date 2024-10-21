<?php

namespace App\Model;

use OpenApi\Attributes as OA;

class EnrichmentVersionPaginationParameters extends PaginationParameters
{
    #[OA\Property(property: 'withTranscript', description: 'With transcript', type: 'string')]
    private bool $withTranscript = true;

    public function getWithTranscript(): bool
    {
        return $this->withTranscript;
    }

    public function setWithTranscript(bool $withTranscript): self
    {
        $this->withTranscript = $withTranscript;

        return $this;
    }
}
