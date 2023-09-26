<?php

namespace App\Model;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[OA\Schema()]
class HealthCheck
{
    #[OA\Property(property: 'kernelStatus', type: 'boolean', description: 'Status of the kernel.')]
    #[SerializedName('kernelStatus')]
    private ?bool $kernelStatus = null;

    #[OA\Property(property: 'dbConnectionStatus', type: 'boolean', description: 'Status of the database connection.')]
    #[SerializedName('dbConnectionStatus')]
    private ?bool $dbConnectionStatus = null;

    public function getKernelStatus(): ?bool
    {
        return $this->kernelStatus;
    }

    public function setKernelStatus(?bool $kernelStatus): self
    {
        $this->kernelStatus = $kernelStatus;

        return $this;
    }

    public function getDbConnectionStatus(): ?bool
    {
        return $this->dbConnectionStatus;
    }

    public function setDbConnectionStatus(?bool $dbConnectionStatus): self
    {
        $this->dbConnectionStatus = $dbConnectionStatus;

        return $this;
    }
}
