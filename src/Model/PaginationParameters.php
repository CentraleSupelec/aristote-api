<?php

namespace App\Model;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class PaginationParameters
{
    #[OA\Property(property: 'sort', description: 'Sort', type: 'string')]
    #[Assert\Choice(
        callback: [Enrichment::class, 'getSortFields'],
        multiple: false,
        message: "The value '{{ value }}' is not valid. Valid choices are: {{ choices }}.",
        groups: ['enrichment']
    )]
    #[Assert\Choice(
        callback: [EnrichmentVersion::class, 'getSortFields'],
        multiple: false,
        message: "The value '{{ value }}' is not valid. Valid choices are: {{ choices }}.",
        groups: ['enrichment_version']
    )]
    private string $sort = 'createdAt';

    #[OA\Property(property: 'order', description: 'Order', type: 'string')]
    #[Assert\Choice(choices: Constants::SORT_ORDER_OPTIONS, multiple: false)]
    private string $order = 'desc';

    #[OA\Property(property: 'size', description: 'Size', type: 'integer')]
    #[Assert\GreaterThanOrEqual(value: 1)]
    private int $size = 50;

    #[OA\Property(property: 'page', description: 'Page', type: 'integer')]
    #[Assert\GreaterThanOrEqual(value: 1)]
    private int $page = 1;

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function setSort(?string $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getOrder(): string
    {
        return strtolower($this->order);
    }

    public function setOrder(string $order): self
    {
        $this->order = strtolower($order);

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }
}
