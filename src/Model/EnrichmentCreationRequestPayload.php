<?php

namespace App\Model;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'notificationWebhookUrl', description: 'A webhook for receiving notifications about the status of enrichment generation', type: 'string')]
    #[Assert\Url(message: 'The given notificationWebhookUrl is not a valid URL')]
    #[Assert\NotBlank(message: 'The parameter notificationWebhookUrl cannot be empty')]
    private ?string $notificationWebhookUrl = null;

    #[OA\Property(property: 'enrichmentParameters', type: 'object', ref: new Model(type: EnrichmentParameters::class))]
    #[Assert\Valid]
    private ?EnrichmentParameters $enrichmentParameters = null;

    #[OA\Property(property: 'endUserIdentifier', description: 'A parameter to help filter enrichments', type: 'string')]
    private ?string $endUserIdentifier = null;

    public function getNotificationWebhookUrl(): ?string
    {
        return $this->notificationWebhookUrl;
    }

    public function setNotificationWebhookUrl(?string $notificationWebhookUrl): self
    {
        $this->notificationWebhookUrl = $notificationWebhookUrl;

        return $this;
    }

    public function getEnrichmentParameters(): ?EnrichmentParameters
    {
        return $this->enrichmentParameters;
    }

    public function setEnrichmentParameters(?EnrichmentParameters $enrichmentParameters): self
    {
        $this->enrichmentParameters = $enrichmentParameters;

        return $this;
    }

    public function getEndUserIdentifier(): ?string
    {
        return $this->endUserIdentifier;
    }

    public function setEndUserIdentifier(?string $endUserIdentifier): self
    {
        $this->endUserIdentifier = $endUserIdentifier;

        return $this;
    }
}
