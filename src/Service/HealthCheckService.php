<?php

namespace App\Service;

use App\Model\HealthCheck;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class HealthCheckService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly KernelInterface $kernel, private readonly LoggerInterface $logger)
    {
    }

    public function check(): HealthCheck
    {
        $healthCheck = new HealthCheck();

        try {
            if ('' !== $this->kernel->getEnvironment()) {
                $healthCheck->setKernelStatus(true);
            } else {
                $healthCheck->setKernelStatus(false);
            }
        } catch (Exception $exception) {
            $healthCheck->setKernelStatus(false);
            $this->logger->error($exception->getMessage());
        }

        try {
            $connection = $this->entityManager->getConnection();

            $result = $connection->executeQuery('SELECT 1');

            if (1 === $result->fetchOne()) {
                $healthCheck->setDbConnectionStatus(true);
            } else {
                $healthCheck->setDbConnectionStatus(false);
            }
        } catch (Exception $exception) {
            $healthCheck->setDbConnectionStatus(false);
            $this->logger->error($exception->getMessage());
        }

        return $healthCheck;
    }
}
