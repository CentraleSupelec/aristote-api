<?php

namespace App\Model;

use OpenApi\Attributes as OA;

#[OA\Schema()]
class ErrorsResponse
{
    #[OA\Property(property: 'status', type: 'string', description: 'KO')]
    private ?string $status = null;

    #[OA\Property(property: 'errors', description: 'Error messages', type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(
            property: 'path',
            description: 'path',
            type: 'string'
        ),
        new OA\Property(
            property: 'message',
            description: 'message',
            type: 'string',
        ),
    ]))]
    private array $errors = [];

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
