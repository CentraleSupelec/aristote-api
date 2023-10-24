<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentParameters
{
    #[OA\Property(property: 'videoTypes', description: 'List of video types', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'videoTypes[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one video type is expected')]
    private array $videoTypes = [];

    #[OA\Property(property: 'disciplines', description: 'List of disciplines', type: 'array', items: new OA\Items(type: 'string'))]
    #[Assert\Type(type: 'array', message: 'Invalid parameter value for \'disciplines[]\', array of strings expected.')]
    #[Assert\Count(min: 1, minMessage: 'At least one discipline is expected')]
    private array $disciplines = [];

    public function getVideoTypes(): array
    {
        return $this->videoTypes;
    }

    public function setVideoTypes(array $videoTypes): self
    {
        $this->videoTypes = $videoTypes;

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
}
