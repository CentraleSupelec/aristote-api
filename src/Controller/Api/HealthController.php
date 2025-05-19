<?php

namespace App\Controller\Api;

use App\Model\HealthCheck as ModelHealthCheck;
use App\Service\HealthCheckService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/v1')]
class HealthController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    #[OA\Get(
        description: 'Can be accessed only by clients with valid tokens for the default scope. Can be used to test authentication.',
        summary: 'Check API default scope',
    )]
    #[OA\Response(
        response: 401,
        description: 'Request rejected because of invalid token supplied'
    )]
    #[OA\Response(
        response: 403,
        description: 'Access denied because of insufficient scope authorization'
    )]
    #[OA\Response(
        response: 202,
        description: 'Authentication succeeded',
        content: new OA\JsonContent(
            properties: [new OA\Property(
                property: 'status',
                description: 'Should be OK',
                type: 'string'
            )],
            type: 'object'
        )
    )]
    #[Route('/home', name: 'api_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->json(['status' => 'OK'], Response::HTTP_ACCEPTED);
    }

    #[OA\Tag(name: 'Health Check')]
    #[OA\Get(
        description: 'Checks kernel environment and database connection. Can be used to test platform status.',
        summary: 'Perform platform health check',
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a health report of kernel and database status',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ModelHealthCheck::class))
        )
    )]
    #[Route('/health', name: 'api_health', methods: ['GET'])]
    public function healthCheck(HealthCheckService $healthCheckService): Response
    {
        $modelHealthCheck = $healthCheckService->check();

        $serialized = $this->serializer->serialize($modelHealthCheck, 'json');

        return new Response($serialized, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
