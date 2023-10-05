<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Tag;
use App\Entity\Topic;
use App\Entity\Transcript;
use App\Entity\Video;
use App\Model\EnrichmentCreationVideoUploadRequestPayload;
use App\Model\EnrichmentCreationVideoUrlRequestPayload;
use App\Model\EnrichmentParameters;
use App\Model\EnrichmentVersionCreationRequestPayload;
use App\Model\ErrorsResponse;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Repository\VideoRepository;
use App\Service\ApiClientManager;
use App\Service\VideoUploadService;
use App\Utils\PaginationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Uuid;
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
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
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

        $paginationParametersErrors = $this->paginationUtils->paginationRequestParametersValidator(Enrichment::getSortFields(), $sort, $order, $size, $page);

        if ([] !== $paginationParametersErrors) {
            return $this->json(['status' => 'KO', 'errors' => $paginationParametersErrors], 400);
        }

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
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Not allowed to access this resource',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
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
        $uuidValidationErrorResponse = $this->validateUuid($id);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateObjectAccess($enrichment, $id);
        if (null !== $enrichmentAccessErrorResponse) {
            return $enrichmentAccessErrorResponse;
        }

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
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'content',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript']))
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
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Not allowed to access this resource',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
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
    public function getEnrichmentVersionsByEnrichmentID(
        string $id,
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository
    ): Response {
        $uuidValidationErrorResponse = $this->validateUuid($id);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $sort = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'DESC');
        $size = $request->query->get('size', 2);
        $page = $request->query->get('page', 1);
        $withTranscript = $request->query->get('withTranscript', 'true');
        $groups = ['enrichment_versions'];

        $paginationParametersErrors = $this->paginationUtils->paginationRequestParametersValidator(EnrichmentVersion::getSortFields(), $sort, $order, $size, $page);

        if ([] !== $paginationParametersErrors) {
            return $this->json(['status' => 'KO', 'errors' => $paginationParametersErrors], 400);
        }

        if ('true' === $withTranscript) {
            $groups[] = 'enrichment_versions_with_transcript';
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateObjectAccess($enrichment, $id);
        if (null !== $enrichmentAccessErrorResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentID($id, $page, $size, $sort, $order);

        $options = [
            AbstractNormalizer::GROUPS => $groups,
        ];

        return $this->json($this->paginationUtils->parsePagination($enrichmentVersions), context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create a new version of an enrichment',
        summary: 'Create a new version of an enrichment'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: EnrichmentVersionCreationRequestPayload::class))
            ),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Created a new version successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'id',
                    description: 'Enrichment version ID',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Not allowed to access this resource',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Enrichment ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{id}/versions', name: 'create_enrichment_version', methods: ['POST'])]
    public function createEnrichmentVersionsByEnrichmentID(
        string $id,
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $uuidValidationErrorResponse = $this->validateUuid($id);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $inputTranscript = $request->files->get('transcript');
        $transcriptContent = json_decode((string) $inputTranscript->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $inputEnrichmentVersionMetadata = json_decode($request->request->get('enrichmentVersionMetadata'), true, 512, JSON_THROW_ON_ERROR);
        $inputMultipleChoiceQuestions = $this->stringJsonObjectsToArray($request->request->get('multipleChoiceQuestions'));

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);
        $initialVersion = 0 === $enrichment->getVersions()->count();

        $enrichmentAccessErrorResponse = $this->validateObjectAccess($enrichment, $id);
        if (null !== $enrichmentAccessErrorResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersionMetadata = (new EnrichmentVersionMetadata())
            ->setDescription($inputEnrichmentVersionMetadata['description'])
            ->setTitle($inputEnrichmentVersionMetadata['title'])
        ;

        foreach ($inputEnrichmentVersionMetadata['tags'] as $tag) {
            $enrichmentVersionMetadata->addTag((new Tag())->setText($tag));
        }

        foreach ($inputEnrichmentVersionMetadata['topics'] as $topic) {
            $enrichmentVersionMetadata->addTopic((new Topic())->setText($topic));
        }

        $enrichmentVersion = (new EnrichmentVersion())
            ->setInitialVersion($initialVersion)
            ->setTranscript((new Transcript())
                ->setLanguage($transcriptContent['language'])
                ->setText($transcriptContent['text'])
                ->setOriginalFilename($inputTranscript->getClientOriginalName())
                ->setSentences(json_encode($transcriptContent['sentences'], JSON_THROW_ON_ERROR))
            )
            ->setEnrichmentVersionMetadata($enrichmentVersionMetadata)
        ;

        foreach ($inputMultipleChoiceQuestions as $inputMultipleChoiceQuestion) {
            $multipleChoiceQuestion = (new MultipleChoiceQuestion())
                ->setQuestion($inputMultipleChoiceQuestion['question'])
                ->setExplanation($inputMultipleChoiceQuestion['explanation'])
            ;

            foreach ($inputMultipleChoiceQuestion['choices'] as $choice) {
                $multipleChoiceQuestion->addChoice((new Choice())
                    ->setCorrectAnswer($choice['correctAnswer'])
                    ->setOptionText($choice['optionText'])
                );
            }
            $enrichmentVersion->addMultipleChoiceQuestion($multipleChoiceQuestion);
        }

        $enrichment->addVersion($enrichmentVersion);

        $errors = $this->validator->validate($enrichment);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }
        $entityManager->persist($enrichment);
        $entityManager->flush();

        return $this->json(['status' => 'OK', 'id' => $enrichmentVersion->getId()]);
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
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Not allowed to access this resource',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Enrichment ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{id}/versions/latest', name: 'latest_enrichment_version', methods: ['GET'])]
    public function getLatestEnrichmentVersionByEnrichmentID(string $id, ApiClientManager $apiClientManager, EnrichmentVersionRepository $enrichmentVersionRepository, EnrichmentRepository $enrichmentRepository): Response
    {
        $uuidValidationErrorResponse = $this->validateUuid($id);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateObjectAccess($enrichment, $id);
        if (null !== $enrichmentAccessErrorResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersions = $enrichmentVersionRepository->findBy(['enrichment' => $id], ['createdAt' => 'DESC']);

        if ([] === $enrichmentVersions) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No version for enrichment with ID '%s' has been found", $id)]], 404);
        }

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
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Not allowed to access this resource',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
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
        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateObjectAccess($enrichmentVersion, $versionId, true);
        if (null !== $enrichmentVersionAccessErrorResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

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
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Delete operation not allowed',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Entity not found',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
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
        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if (null !== $uuidValidationErrorResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateObjectAccess($enrichmentVersion, $versionId, true);
        if (null !== $enrichmentVersionAccessErrorResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

        if ($enrichmentVersion->isInitialVersion()) {
            return $this->json(['status' => 'KO', 'errors' => "Can't delete initial version"], 403);
        }

        $entityManager->remove($enrichmentVersion);
        $entityManager->flush();

        return $this->json(['status' => 'OK']);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create an enrichment from a video URL (accessible without authentication)',
        summary: 'Create an enrichment from a video URL (accessible without authentication)'
    )]
    #[OA\RequestBody(
        description: 'Parameters defining the ldap accounts which we want to retrieve',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentCreationVideoUrlRequestPayload::class),
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'File uploaded successfully, enrichment will be started as soon as possible',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'id',
                    description: 'Enrichment ID',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request, invalid data provided',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[Route('/enrichments/video-urls/upload', name: 'create_enrichment_from_video_url', methods: ['POST'])]
    public function createEnrichmentFromVideoUrl(Request $request, VideoRepository $videoRepository, VideoUploadService $videoUploadService): Response
    {
        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $enrichmentCreationRequestPayload = (new EnrichmentCreationVideoUrlRequestPayload())
            ->setVideoUrl($content['videoUrl'])
            ->setNotificationWebhookUrl($content['notificationWebhookUrl'])
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($content['enrichmentParameters']['disciplines'] ?? [])
                ->setVideoTypes($content['enrichmentParameters']['videoTypes'] ?? [])
            )
        ;

        $errors = $this->validator->validate($enrichmentCreationRequestPayload);
        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $videoUrl = $enrichmentCreationRequestPayload->getVideoUrl();

        try {
            $videoName = $this->getVideoFromUrl($videoUrl);
        } catch (Exception $exception) {
            return $this->json(['status' => 'KO', 'errors' => [$exception->getMessage()]]);
        }

        $temporaryVideoPath = sprintf('%s/%s', Constants::TEMPORARY_STORAGE_PATH, $videoName);

        try {
            $uploadedFile = new UploadedFile($temporaryVideoPath, $videoUrl);
            $videoOrErrorsArray = $videoUploadService->uploadVideo($uploadedFile);
            unlink($temporaryVideoPath);
            if (!$videoOrErrorsArray instanceof Video) {
                return $this->json(['status' => 'KO', 'errors' => $videoOrErrorsArray], 400);
            }
        } catch (Exception $exception) {
            if (file_exists($temporaryVideoPath)) {
                unlink($temporaryVideoPath);
            }

            return $this->json(['status' => 'KO', 'errors' => [$exception->getMessage()]], 400);
        }

        return $this->json(['status' => 'OK', 'id' => $videoOrErrorsArray->getEnrichment()->getId()]);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create an enrichment from a video URL (accessible without authentication)',
        summary: 'Create an enrichment from a video URL (accessible without authentication)'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: EnrichmentCreationVideoUploadRequestPayload::class))
            ),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'File uploaded successfully, enrichment will be started as soon as possible',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'id',
                    description: 'Enrichment ID',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request, invalid data provided',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
    )]
    #[Route('/enrichments/videos/upload', name: 'create_enrichment_from_uploaded_video', methods: ['POST'])]
    public function createEnrichmentFromUploadedVideo(Request $request, VideoUploadService $videoUploadService): Response
    {
        /** @var UploadedFile $videoFile */
        $videoFile = $request->files->get('videoFile');
        $inputEnrichmentParameters = json_decode($request->request->get('enrichmentParameters'), true, 512, JSON_THROW_ON_ERROR);
        $enrichmentCreationRequestPayload = (new EnrichmentCreationVideoUploadRequestPayload())
            ->setVideoFile($videoFile)
            ->setNotificationWebhookUrl($request->request->get('notificationWebhookUrl'))
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($inputEnrichmentParameters['disciplines'] ?? [])
                ->setVideoTypes($inputEnrichmentParameters['videoTypes'] ?? [])
            )
        ;

        $errors = $this->validator->validate($enrichmentCreationRequestPayload);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $videoOrErrorsArray = $videoUploadService->uploadVideo($videoFile);

        if (!$videoOrErrorsArray instanceof Video) {
            return $this->json(['status' => 'KO', 'errors' => $videoOrErrorsArray], 400);
        }

        return $this->json(['status' => 'OK', 'id' => $videoOrErrorsArray->getEnrichment()->getId()]);
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new Uuid());

        if ($constraintViolationList->count() > 0) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("'%s' is not a valid UUID", $id)]], 400);
        }

        return null;
    }

    private function validateObjectAccess(Enrichment|EnrichmentVersion|null $object, string $id, bool $objectIntendedToBeEnrichmentVersion = false): ?JsonResponse
    {
        $objectName = sprintf('enrichment%s', $objectIntendedToBeEnrichmentVersion ? ' version' : '');
        if (null === $object) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No %s with ID '%s' has been found", $objectName, $id)]], 404);
        }

        $enrichment = $object instanceof EnrichmentVersion ? $object->getEnrichment() : $object;

        if ($enrichment->getCreatedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf('You are not allowed to access this %s', $objectName, $id)]], 403);
        }

        return null;
    }

    private function getVideoFromUrl(string $videoUrl): string
    {
        $fileName = uniqid('video_').'.mp4';

        if (!is_dir(Constants::TEMPORARY_STORAGE_PATH)) {
            mkdir(Constants::TEMPORARY_STORAGE_PATH, 0777, true);
        }

        $videoContents = file_get_contents($videoUrl);
        file_put_contents(sprintf('%s/%s', Constants::TEMPORARY_STORAGE_PATH, $fileName), $videoContents);

        return $fileName;
    }

    private function stringJsonObjectsToArray(string $jsonString)
    {
        if (str_starts_with($jsonString, '[')) {
            return json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } else {
            return json_decode(sprintf('[%s]', $jsonString), true, 512, JSON_THROW_ON_ERROR);
        }
    }
}
