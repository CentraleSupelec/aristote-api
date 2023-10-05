<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'notificationWebhookUrl', description: 'A webhook for receiving notifications about the status of enrichment generation', type: 'string')]
    #[Assert\Url(message: 'The given notificationWebhookUrl is not a valid URL')]
    #[Assert\NotBlank(message: 'The parameter notificationWebhookUrl cannot be empty')]
    private ?string $notificationWebhookUrl = null;

    #[Assert\Valid]
    private ?EnrichmentParameters $enrichmentParameters = null;

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
}
