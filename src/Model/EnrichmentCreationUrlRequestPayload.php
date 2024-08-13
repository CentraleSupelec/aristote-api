<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationUrlRequestPayload extends EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'url', description: 'A file URL', type: 'string')]
    #[Groups(['Default'])]
    #[Assert\Url(message: 'The given url is not a valid')]
    #[Assert\NotBlank]
    private ?string $url = null;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
