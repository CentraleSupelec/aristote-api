<?php

namespace App\Model;

use App\Constants;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema()]
class EnrichmentCreationRequestPayload
{
    #[OA\Property(property: 'notificationWebhookUrl', description: 'A webhook for receiving notifications about the status of enrichment generation', type: 'string')]
    #[Assert\Url(requireTld: true, message: 'The given notificationWebhookUrl is not a valid URL')]
    #[Assert\NotBlank(message: 'The parameter notificationWebhookUrl cannot be empty')]
    #[Groups(['Default'])]
    private ?string $notificationWebhookUrl = null;

    #[OA\Property(property: 'notificationLevel', description: 'Choose to receive notifications only at the end of the enrichment or at each step of the process', type: 'string', nullable: true)]
    #[Assert\Choice(callback: [Constants::class, 'getNotificationLevels'], multiple: false, message: 'Invalid notificationLevel value')]
    #[Groups(['Default'])]
    private ?string $notificationLevel = null;

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

    public function getNotificationLevel(): ?string
    {
        return $this->notificationLevel;
    }

    public function setNotificationLevel(?string $notificationLevel): static
    {
        $this->notificationLevel = $notificationLevel;

        return $this;
    }

    public function getEnrichmentParameters(): ?EnrichmentParameters
    {
        return $this->enrichmentParameters;
    }

    public function setEnrichmentParameters(EnrichmentParameters $enrichmentParameters): static
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
