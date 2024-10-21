<?php

namespace App\Model;

use OpenApi\Attributes as OA;

class EnrichmentPaginationParameters extends PaginationParameters
{
    #[OA\Property(property: 'endUserIdentifier', description: 'End User Identifier', type: 'string')]
    private ?string $endUserIdentifier = null;

    #[OA\Property(property: 'withStatus', description: 'With status', type: 'string')]
    private bool $withStatus = false;

    public function getEndUserIdentifier(): ?string
    {
        return $this->endUserIdentifier;
    }

    public function setEndUserIdentifier(?string $endUserIdentifier): self
    {
        $this->endUserIdentifier = $endUserIdentifier;

        return $this;
    }

    public function getWithStatus(): bool
    {
        return $this->withStatus;
    }

    public function setWithStatus(bool $withStatus): self
    {
        $this->withStatus = $withStatus;

        return $this;
    }
}
