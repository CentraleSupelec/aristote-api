<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'notificationWebhookUrl', description: 'A webhook for receiving notifications about the status of enrichment generation', type: 'string')]
    #[Assert\Url(message: 'The given notificationWebhookUrl is not a valid URL')]
    #[Assert\NotBlank(message: 'The parameter notificationWebhookUrl cannot be empty')]
    #[Groups(['Default'])]
    private ?string $notificationWebhookUrl = null;

    #[OA\Property(property: 'enrichmentParameters', type: 'object', ref: new Model(type: EnrichmentParameters::class))]
    #[Assert\Valid]
    #[Groups(['Default'])]
    private ?EnrichmentParameters $enrichmentParameters = null;

    #[OA\Property(property: 'endUserIdentifier', description: 'A parameter to help filter enrichments', type: 'string')]
    #[Groups(['Default'])]
    private ?string $endUserIdentifier = null;

    public function getNotificationWebhookUrl(): ?string
    {
        return $this->notificationWebhookUrl;
    }

    public function setNotificationWebhookUrl(?string $notificationWebhookUrl): static
    {
        $this->notificationWebhookUrl = $notificationWebhookUrl;

        return $this;
    }

    public function getEnrichmentParameters(): ?EnrichmentParameters
    {
        return $this->enrichmentParameters;
    }

    public function setEnrichmentParameters(?EnrichmentParameters $enrichmentParameters): static
    {
        $this->enrichmentParameters = $enrichmentParameters;

        return $this;
    }

    public function getEndUserIdentifier(): ?string
    {
        return $this->endUserIdentifier;
    }

    public function setEndUserIdentifier(?string $endUserIdentifier): static
    {
        $this->endUserIdentifier = $endUserIdentifier;

        return $this;
    }
}
