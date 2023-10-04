<?php

namespace App\Controller\Api;

use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Service\ApiClientManager;
use App\Utils\PaginationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1')]
class EnrichmentsController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils
    ) {
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Enable a user to list, with pagination, all of his own created enrichment',
        summary: 'Enable a user to list, with pagination, all of his own created enrichment'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a list of enrichments created by the user',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'content',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Enrichment::class, groups: ['enrichments']))
                ),
                new OA\Property(
                    property: 'isLastPage',
                    description: 'Returns true if this is the last page, false otherwise',
                    type: 'boolean'
                ),
                new OA\Property(
                    property: 'currentPage',
                    description: 'Returns the current page number',
                    type: 'integer', format: 'int64'
                ),

                new OA\Property(
                    property: 'totalElements',
                    description: 'Returns the total number of elements',
                    type: 'integer', format: 'int64'
                ),
            ]
        )
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Sort property to use',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort oder to use (DESC or ASC)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'size',
        in: 'query',
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[Route('/enrichments', name: 'enrichments', methods: ['GET'])]
    public function getEnrichments(Request $request, ApiClientManager $apiClientManager, EnrichmentRepository $enrichmentRepository): Response
    {
        $sort = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'DESC');
        $size = $request->query->get('size', 50);
        $page = $request->query->get('page', 1);

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $enrichments = $enrichmentRepository->findByCreatedBy($clientEntity->getIdentifier(), $page, $size, $sort, $order);

        $options = [
            AbstractNormalizer::GROUPS => ['enrichments'],
        ];

        return $this->json($this->paginationUtils->parsePagination($enrichments), context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Get enrichment status by id',
        summary: 'Get enrichment status by id'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns an enrichment',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: Enrichment::class, groups: ['enrichments'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Enrichment ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{id}', name: 'enrichment', methods: ['GET'])]
    public function getEnrichmentByID(string $id, ApiClientManager $apiClientManager, EnrichmentRepository $enrichmentRepository): Response
    {
        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $options = [
            AbstractNormalizer::GROUPS => ['enrichments'],
        ];

        return $this->json($enrichment, context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Get enrichment versions by enrichment id',
        summary: 'Get enrichment versions by enrichment id'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a list of enrichment versions',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript']))
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Enrichment ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Sort property to use',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort oder to use (DESC or ASC)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'size',
        in: 'query',
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\Parameter(
        name: 'withTranscript',
        in: 'query',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[Route('/enrichments/{id}/versions', name: 'enrichment_versions', methods: ['GET'])]
    public function getEnrichmentVersionsByEnrichmentID(string $id, Request $request, ApiClientManager $apiClientManager, EnrichmentVersionRepository $enrichmentVersionRepository): Response
    {
        $sort = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'DESC');
        $size = $request->query->get('size', 2);
        $page = $request->query->get('page', 1);
        $withTranscript = $request->query->get('withTranscript', 'true');
        $groups = ['enrichment_versions'];

        if ('true' === $withTranscript) {
            $groups[] = 'enrichment_versions_with_transcript';
        }

        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentID($id, $page, $size, $sort, $order);

        $options = [
            AbstractNormalizer::GROUPS => $groups,
        ];

        return $this->json($this->paginationUtils->parsePagination($enrichmentVersions), context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: "Get enrichment's latest version",
        summary: "Get enrichment's latest version"
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the latest enrichment version of the enrichment',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Enrichment ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{id}/versions/latest', name: 'latest_enrichment_version', methods: ['GET'])]
    public function getLatestEnrichmentVersionByEnrichmentID(string $id, ApiClientManager $apiClientManager, EnrichmentVersionRepository $enrichmentVersionRepository): Response
    {
        $enrichmentVersions = $enrichmentVersionRepository->findBy(['enrichment' => $id], ['createdAt' => 'DESC']);

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json($enrichmentVersions[0], context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Get the details of an enrichment version',
        summary: 'Get the details of an enrichment version'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the details of the enrichment version',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript'])
        )
    )]
    #[OA\Parameter(
        name: 'versionId',
        description: 'Enrichment version ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/versions/{versionId}', name: 'enrichment_version', methods: ['GET'])]
    public function getEnrichmentVersionByID(string $versionId, ApiClientManager $apiClientManager, EnrichmentVersionRepository $enrichmentVersionRepository): Response
    {
        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json($enrichmentVersion, context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Delete an enrichment version',
        summary: 'Delete an enrichment version'
    )]
    #[OA\Response(
        response: 204,
        description: 'Successful delete operation',
        content: new OA\JsonContent(
            properties: [new OA\Property(
                property: 'status',
                description: 'OK',
                type: 'string'
            )],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Unsuccessful delete operation',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'KO',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'error',
                    description: 'Error message',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Parameter(
        name: 'versionId',
        description: 'Enrichment version ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/versions/{versionId}', name: 'delete_enrichment_version', methods: ['DELETE'])]
    public function deleteEnrichmentVersion(string $versionId, EnrichmentVersionRepository $enrichmentVersionRepository, EntityManagerInterface $entityManager): Response
    {
        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        if ($enrichmentVersion->isInitialVersion()) {
            return $this->json(['status' => 'KO', 'error' => "Can't delete initial version"], 403);
        }

        $entityManager->remove($enrichmentVersion);
        $entityManager->flush();

        return $this->json(['status' => 'OK']);
    }
}
