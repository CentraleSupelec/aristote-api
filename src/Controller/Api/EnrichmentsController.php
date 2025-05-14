<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\AiModel;
use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\Infrastructure;
use App\Entity\Media;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Exception\MediaDurationExceedsLimitException;
use App\Exception\TextLengthExceedsLimitException;
use App\Exception\UploadFileUnsupportedTypeException;
use App\Message\FileUploadFromUrlMessage;
use App\Model\EnrichmentCreationFileUploadRequestPayload;
use App\Model\EnrichmentCreationRequestPayload;
use App\Model\EnrichmentCreationUrlRequestPayload;
use App\Model\EnrichmentPaginationParameters;
use App\Model\EnrichmentParameters;
use App\Model\EnrichmentVersionCreationRequestPayload;
use App\Model\EnrichmentVersionPaginationParameters;
use App\Model\EnrichmentVersionUserEvaluationRequestPayload;
use App\Model\ErrorsResponse;
use App\Repository\ApiClientRepository;
use App\Repository\ChoiceRepository;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Repository\MultipleChoiceQuestionRepository;
use App\Service\ApiClientManager;
use App\Service\FileUploadService;
use App\Service\ScopeAuthorizationCheckerService;
use App\Utils\EnrichmentUtils;
use App\Utils\MimeTypeUtils;
use App\Utils\PaginationUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1')]
class EnrichmentsController extends AbstractController
{
    public function __construct(
        private readonly bool $autoDeleteMediaAfterTranscription,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils,
        private readonly ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
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
                    items: new OA\Items(ref: new Model(type: Enrichment::class, groups: ['enrichments', 'enrichments_with_status']))
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
    #[OA\Parameter(
        name: 'endUserIdentifier',
        description: 'End User Identifier',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'withStatus',
        description: 'Get Statuses information',
        in: 'query',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[Route('/enrichments', name: 'enrichments', methods: ['GET'], options: ['expose' => true])]
    public function getEnrichments(
        #[MapQueryString(validationGroups: 'no_validation')] ?EnrichmentPaginationParameters $enrichmentPaginationParameters,
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        if ($enrichmentPaginationParameters instanceof EnrichmentPaginationParameters) {
            $errors = $this->validator->validate($enrichmentPaginationParameters, groups: ['Default', 'enrichment']);

            if (count($errors) > 0) {
                $errorsArray = array_map(fn (ConstraintViolation $error) => [
                    'message' => $error->getMessage(),
                    'path' => $error->getPropertyPath(),
                ], iterator_to_array($errors));

                return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
            }
        } else {
            $enrichmentPaginationParameters = new EnrichmentPaginationParameters();
        }

        $groups = ['enrichments'];

        if ($enrichmentPaginationParameters->getWithStatus()) {
            $groups[] = 'enrichments_with_status';
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $enrichments = $enrichmentRepository->findByCreatedBy(
            $clientEntity->getIdentifier(),
            $enrichmentPaginationParameters->getPage(),
            $enrichmentPaginationParameters->getSize(),
            $enrichmentPaginationParameters->getSort(),
            $enrichmentPaginationParameters->getOrder(),
            $enrichmentPaginationParameters->getEndUserIdentifier()
        );

        $options = [
            AbstractNormalizer::GROUPS => $groups,
        ];

        return $this->json($this->paginationUtils->parsePagination($enrichments), context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Get available AI Model-Infrastructure combinations',
        summary: 'Get available AI Model-Infrastructure combinations'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a list of possible AI Models/Infrastructure pairs',
        content: new OA\JsonContent(
            type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(
                    property: 'aiModel',
                    description: 'Enrichment AI Model',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'infrastructure',
                    description: 'Infrastructure',
                    type: 'string',
                ),
            ])
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
    #[Route('/enrichments/ai_model_infrastructure_combinations', name: 'ai_model_infrastructure_combinations', methods: ['GET'])]
    public function getAiModelInfrastructureCombinations(ApiClientRepository $apiClientRepository, ApiClientManager $apiClientManager): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $cominations = $apiClientRepository->getDistinctCombinations($clientEntity->getEnrichmentModel(), $clientEntity->getEnrichmentInfrastructure());

        return $this->json($cominations);
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
    public function getEnrichmentByID(string $id, EnrichmentRepository $enrichmentRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $options = [
            AbstractNormalizer::GROUPS => ['enrichments', 'enrichments_with_status'],
        ];

        return $this->json($enrichment, context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Delete(
        description: 'Delete enrichment by id',
        summary: 'Delete enrichment by id'
    )]
    #[OA\Response(
        response: 200,
        description: 'Enrichment deleted successfully',
        content: new OA\JsonContent(
            properties: [
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
    #[Route('/enrichments/{id}', name: 'delete_enrichment', methods: ['DELETE'])]
    public function deleteEnrichmentByID(
        string $id,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        EnrichmentUtils $enrichmentUtils,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to delete this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        if ($enrichment->getMedia() instanceof Media) {
            $enrichment->getMedia()->setOriginalFileName(null);
        }

        $enrichment
            ->setDeleted(true)
            ->setDisciplines(null)
            ->setMediaTypes(null)
            ->setNotificationWebhookUrl(null)
            ->setMediaUrl(null)
            ->setDeletedAt(new DateTime())
            ->setEvaluationMark($enrichmentUtils->calculateEvaluationMark($enrichment))
            ->setInitialEnrichmentVersion(null)
            ->setLastEnrichmentVersion(null)
        ;

        $versions = $enrichment->getVersions();

        foreach ($versions as $version) {
            $entityManager->remove($version);
        }

        $entityManager->flush();

        return $this->json(['status' => 'OK']);
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
        #[MapQueryString(validationGroups: 'no_validation')] ?EnrichmentVersionPaginationParameters $enrichmentVersionPaginationParameters,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        if ($enrichmentVersionPaginationParameters instanceof EnrichmentVersionPaginationParameters) {
            $errors = $this->validator->validate($enrichmentVersionPaginationParameters, groups: ['Default', 'enrichment_version']);

            if (count($errors) > 0) {
                $errorsArray = array_map(fn (ConstraintViolation $error) => [
                    'message' => $error->getMessage(),
                    'path' => $error->getPropertyPath(),
                ], iterator_to_array($errors));

                return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
            }
        } else {
            $enrichmentVersionPaginationParameters = (new EnrichmentVersionPaginationParameters())->setSize(2);
        }

        $groups = ['enrichment_versions'];

        if ($enrichmentVersionPaginationParameters->getWithTranscript()) {
            $groups[] = 'enrichment_versions_with_transcript';
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentId(
            $id,
            $enrichmentVersionPaginationParameters->getPage(),
            $enrichmentVersionPaginationParameters->getSize(),
            $enrichmentVersionPaginationParameters->getSort(),
            $enrichmentVersionPaginationParameters->getOrder()
        );

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
        #[MapRequestPayload(validationGroups: 'no_validation')] EnrichmentVersionCreationRequestPayload $enrichmentVersionCreationRequestPayload,
        Request $request,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $transcript = $request->files->get('transcript');

        $errors = $this->validator->validate($enrichmentVersionCreationRequestPayload->setTranscript($transcript));

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $initialVersion = 0 === $enrichment->getVersions()->count();

        if ($initialVersion) {
            return $this->json([
                'status' => 'KO',
                'errors' => [
                    [
                        'path' => 'id',
                        'message' => 'No initial version found for the enrichment. Please wait for the generation of the initial version before pushing a new one',
                    ],
                ],
            ], 403);
        }

        if (($inputTranscript = $enrichmentVersionCreationRequestPayload->getTranscript()) instanceof UploadedFile) {
            $transcriptContent = json_decode($inputTranscript->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $newTranscript = (new Transcript())
                ->setLanguage($transcriptContent['language'])
                ->setText($transcriptContent['text'])
                ->setOriginalFilename($inputTranscript->getClientOriginalName())
                ->setSentences(json_encode($transcriptContent['sentences'], JSON_THROW_ON_ERROR))
            ;
        } else {
            $latestVersionTranscript = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichment->getId())->getTranscript();
            $newTranscript = (new Transcript())
                ->setText($latestVersionTranscript->getText())
                ->setLanguage($latestVersionTranscript->getLanguage())
                ->setOriginalFilename($latestVersionTranscript->getOriginalFilename())
                ->setSentences($latestVersionTranscript->getSentences())
            ;
        }

        $enrichmentVersion = (new EnrichmentVersion())
            ->setTranscript($newTranscript)
            ->setDisciplines($enrichment->getDisciplines())
            ->setMediaTypes($enrichment->getMediaTypes())
            ->setLanguage($enrichment->getLanguage())
            ->setTranslateTo($enrichment->getTranslateTo())
            ->setEnrichmentVersionMetadata($enrichmentVersionCreationRequestPayload->getEnrichmentVersionMetadata())
            ->setNotes($enrichmentVersionCreationRequestPayload->getNotes())
            ->setTranslatedNotes($enrichmentVersionCreationRequestPayload->getTranslatedNotes())
        ;

        $enrichment
            ->setEnrichmentRetries(0)
            ->setTranslationRetries(0)
            ->setEvaluationRetries(0)
        ;

        if ($enrichmentVersionCreationRequestPayload->getTranslate()) {
            $enrichment
                ->setStatus(Enrichment::STATUS_WAITING_TRANSLATION)
                ->setTranslatedBy(null)
                ->setTranslationStartedAt(null)
                ->setTranslationEndedAt(null)
                ->setTranslationTaskId(null)
                ->setAiEvaluation(null)
            ;
        }

        foreach ($enrichmentVersionCreationRequestPayload->getMultipleChoiceQuestions() as $multipleChoiceQuestion) {
            $enrichmentVersion->addMultipleChoiceQuestion($multipleChoiceQuestion);
        }

        $enrichment->addVersion($enrichmentVersion);
        $enrichment->setLastEnrichmentVersion($enrichmentVersion);

        $errors = $this->validator->validate($enrichment, groups: ['Default']);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

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
    public function getLatestEnrichmentVersionByEnrichmentID(string $id, EnrichmentVersionRepository $enrichmentVersionRepository, EnrichmentRepository $enrichmentRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersions = $enrichmentVersionRepository->findBy(['enrichment' => $id], ['createdAt' => 'DESC']);

        if ([] === $enrichmentVersions) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("No version for enrichment with ID '%s' has been found", $id),
                ],
            ]], 404);
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}', name: 'enrichment_version', methods: ['GET'])]
    public function getEnrichmentVersionByID(string $enrichmentId, string $versionId, EnrichmentRepository $enrichmentRepository, EnrichmentVersionRepository $enrichmentVersionRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess($enrichmentVersion, $versionId, $enrichmentId);
        if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}', name: 'delete_enrichment_version', methods: ['DELETE'])]
    public function deleteEnrichmentVersion(string $enrichmentId, string $versionId, EnrichmentRepository $enrichmentRepository, EnrichmentVersionRepository $enrichmentVersionRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess($enrichmentVersion, $versionId, $enrichmentId);
        if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

        if ($enrichmentVersion->isInitialVersion()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'versionId',
                    'message' => "Can't delete initial version",
                ],
            ]], 403);
        }

        $entityManager->remove($enrichmentVersion);
        $entityManager->flush();

        $latestEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichmentId);
        $enrichment = $enrichmentRepository->findOneBy(['id' => $enrichmentId]);
        $enrichment
            ->setLastEnrichmentVersion($latestEnrichmentVersion)
            ->setEnrichmentRetries($latestEnrichmentVersion->getEnrichmentRetries())
            ->setTranslationRetries($latestEnrichmentVersion->getTranslationRetries())
            ->setEvaluationRetries($latestEnrichmentVersion->getEvaluationRetries())
        ;

        $entityManager->flush();

        return $this->json(['status' => 'OK']);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create an enrichment from a URL (accessible without authentication)',
        summary: 'Create an enrichment from a URL (accessible without authentication)'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentCreationUrlRequestPayload::class, groups: ['Default', 'treatments']),
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
    #[Route('/enrichments/url', name: 'create_enrichment_from_url', methods: ['POST'])]
    public function createEnrichmentFromUrl(
        #[MapRequestPayload(validationGroups: 'no_validation')] EnrichmentCreationUrlRequestPayload $enrichmentCreationUrlRequestPayload,
        MessageBusInterface $messageBus,
        ApiClientManager $apiClientManager,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $groups = ['Default'];

        if ($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getGenerateMetadata()) {
            $groups[] = 'metadata';
        }

        $errors = $this->validator->validate($enrichmentCreationUrlRequestPayload, groups: $groups);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        if (null === $enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getAiModel() && $clientEntity->getEnrichmentModel() instanceof AiModel) {
            $enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->setAiModel($clientEntity->getEnrichmentModel()->getName());
        }

        if (null === $enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getInfrastructure() && $clientEntity->getEnrichmentInfrastructure() instanceof Infrastructure) {
            $enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->setInfrastructure($clientEntity->getEnrichmentInfrastructure()->getName());
        }

        $enrichment = (new Enrichment())
            ->setStatus(Enrichment::STATUS_WAITING_MEIDA_UPLOAD)
            ->setCreatedBy($clientEntity)
            ->setLatestEnrichmentRequestedAt(new DateTime())
            ->setMediaUrl($enrichmentCreationUrlRequestPayload->getUrl())
            ->setNotificationWebhookUrl($enrichmentCreationUrlRequestPayload->getNotificationWebhookUrl())
            ->setNotificationLevel($enrichmentCreationUrlRequestPayload->getNotificationLevel())
            ->setDisciplines($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getDisciplines())
            ->setMediaTypes($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getMediaTypes())
            ->setAiEvaluation($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getAiEvaluation())
            ->setEndUserIdentifier($enrichmentCreationUrlRequestPayload->getEndUserIdentifier())
            ->setAiModel($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getAiModel())
            ->setInfrastructure($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getInfrastructure())
            ->setLanguage($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getLanguage())
            ->setTranslateTo($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getTranslateTo())
            ->setGenerateMetadata($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getGenerateMetadata())
            ->setGenerateQuiz($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getGenerateQuiz())
            ->setGenerateNotes($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getGenerateNotes())
        ;

        $errors = $this->validator->validate($enrichment, groups: $groups);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $entityManager->persist($enrichment);
        $entityManager->flush();

        $messageBus->dispatch(new FileUploadFromUrlMessage($enrichment->getId(), $clientEntity, $enrichmentCreationUrlRequestPayload));

        return $this->json(['status' => 'OK', 'id' => $enrichment->getId()]);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create an enrichment from a file',
        summary: 'Supported : video, audio and subtitle (srt, vtt) files'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: EnrichmentCreationFileUploadRequestPayload::class, groups: ['Default', 'treatments']))
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
    #[Route('/enrichments/upload', name: 'create_enrichment_from_uploaded_file', methods: ['POST'])]
    public function createEnrichmentFromUploadedFile(
        Request $request,
        FileUploadService $fileUploadService,
        ApiClientManager $apiClientManager,
        EntityManagerInterface $entityManager,
        FilesystemOperator $mediaStorage,
        MimeTypeUtils $mimeTypeUtils,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $originalFileName = $request->request->get('originalFileName');
        if ($originalFileName) {
            $file = new UploadedFile($file->getPathname(), $originalFileName, $file->getMimeType());
        }

        $inputEnrichmentParameters = json_decode($request->request->get('enrichmentParameters'), true, 512, JSON_THROW_ON_ERROR);
        $endUserIdentifier = $request->request->get('endUserIdentifier');
        $aiEvaluation = $inputEnrichmentParameters['aiEvaluation'] ?? null;
        $aiModel = $inputEnrichmentParameters['aiModel'] ?? null;
        $infrastructure = $inputEnrichmentParameters['infrastructure'] ?? null;
        $language = $inputEnrichmentParameters['language'] ?? null;
        $translateTo = $inputEnrichmentParameters['translateTo'] ?? null;
        $generateMetadata = $inputEnrichmentParameters['generateMetadata'] ?? true;
        $generateQuiz = $inputEnrichmentParameters['generateQuiz'] ?? true;
        $generateNotes = $inputEnrichmentParameters['generateNotes'] ?? false;

        $enrichmentCreationFileUploadRequestPayload = (new EnrichmentCreationFileUploadRequestPayload())
            ->setFile($file)
            ->setNotificationWebhookUrl($request->request->get('notificationWebhookUrl'))
            ->setNotificationLevel($request->request->get('notificationLevel'))
            ->setEndUserIdentifier(null === $endUserIdentifier || '' === $endUserIdentifier ? null : $endUserIdentifier)
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($inputEnrichmentParameters['disciplines'] ?? [])
                ->setMediaTypes($inputEnrichmentParameters['mediaTypes'] ?? [])
                ->setAiEvaluation(null === $aiEvaluation || '' === $aiEvaluation ? null : $aiEvaluation)
                ->setAiModel(null === $aiModel || '' === $aiModel ? null : $aiModel)
                ->setInfrastructure(null === $infrastructure || '' === $infrastructure ? null : $infrastructure)
                ->setLanguage(null === $language || '' === $language ? null : $language)
                ->setTranslateTo(null === $translateTo || '' === $translateTo ? null : $translateTo)
                ->setGenerateMetadata($generateMetadata)
                ->setGenerateQuiz($generateQuiz)
                ->setGenerateNotes($generateNotes)
            )
        ;

        $groups = ['Default'];

        if ($generateMetadata) {
            $groups[] = 'metadata';
        }

        $errors = $this->validator->validate($enrichmentCreationFileUploadRequestPayload, groups: $groups);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        if (null === $enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getAiModel() && $clientEntity->getEnrichmentModel() instanceof AiModel) {
            $enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->setAiModel($clientEntity->getEnrichmentModel()->getName());
        }

        if (null === $enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getInfrastructure() && $clientEntity->getEnrichmentInfrastructure() instanceof Infrastructure) {
            $enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->setInfrastructure($clientEntity->getEnrichmentInfrastructure()->getName());
        }

        $enrichment = (new Enrichment())
                ->setCreatedBy($clientEntity)
                ->setLatestEnrichmentRequestedAt(new DateTime())
                ->setNotificationWebhookUrl($enrichmentCreationFileUploadRequestPayload->getNotificationWebhookUrl())
                ->setNotificationLevel($enrichmentCreationFileUploadRequestPayload->getNotificationLevel())
                ->setDisciplines($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getDisciplines())
                ->setMediaTypes($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getMediaTypes())
                ->setAiEvaluation($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getAiEvaluation())
                ->setEndUserIdentifier($enrichmentCreationFileUploadRequestPayload->getEndUserIdentifier())
                ->setAiModel($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getAiModel())
                ->setInfrastructure($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getInfrastructure())
                ->setLanguage($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getLanguage())
                ->setTranslateTo($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getTranslateTo())
                ->setGenerateMetadata($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getGenerateMetadata())
                ->setGenerateQuiz($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getGenerateQuiz())
                ->setGenerateNotes($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getGenerateNotes())
        ;

        try {
            $enrichment = $fileUploadService->uploadFile($file, $clientEntity, $enrichment);
        } catch (UploadFileUnsupportedTypeException|MediaDurationExceedsLimitException|TextLengthExceedsLimitException $exception) {
            return $this->json([
                'status' => 'KO',
                'errors' => [
                    [
                        'path' => 'file',
                        'message' => $exception->getMessage(),
                    ],
                ],
            ], 400);
        }

        $errors = $this->validator->validate($enrichment, groups: $groups);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json([
                'status' => 'KO',
                'errors' => $errorsArray,
            ], 400);
        }
        $entityManager->persist($enrichment);
        $entityManager->flush();

        if ($this->autoDeleteMediaAfterTranscription && $mimeTypeUtils->isSubtitleFile($enrichment->getMedia()->getMimeType())) {
            $mediaStorage->delete($enrichment->getMedia()->getFileDirectory().'/'.$enrichment->getMedia()->getFileName());
        }

        return $this->json(['status' => 'OK', 'id' => $enrichment->getId()]);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create new enrichment version by AI',
        summary: 'Create new enrichment version by AI'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentCreationRequestPayload::class, groups: ['Default', 'treatments']),
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Create new enrichment version by AI request sent',
        content: new OA\JsonContent(
            properties: [
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
    #[Route('/enrichments/{enrichmentId}/new_ai_version', name: 'create_new_ai_enrichment', methods: ['POST'])]
    public function createNewAiEnrichment(
        string $enrichmentId,
        #[MapRequestPayload(validationGroups: 'no_validation')] EnrichmentCreationRequestPayload $enrichmentCreationRequestPayload,
        EntityManagerInterface $entityManager,
        EnrichmentRepository $enrichmentRepository,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        ApiClientManager $apiClientManager,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichment = $enrichmentRepository->findOneBy(['id' => $enrichmentId]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $enrichmentId);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $errors = $this->validator->validate($enrichmentCreationRequestPayload);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        if (null === $enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiModel() && $clientEntity->getEnrichmentModel() instanceof AiModel) {
            $enrichmentCreationRequestPayload->getEnrichmentParameters()->setAiModel($clientEntity->getEnrichmentModel()->getName());
        }

        if (null === $enrichmentCreationRequestPayload->getEnrichmentParameters()->getInfrastructure() && $clientEntity->getEnrichmentInfrastructure() instanceof Infrastructure) {
            $enrichmentCreationRequestPayload->getEnrichmentParameters()->setInfrastructure($clientEntity->getEnrichmentInfrastructure()->getName());
        }

        $targetStatus = Enrichment::STATUS_SUCCESS;
        if ($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateMetadata() || $enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateQuiz() || $enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateNotes()) {
            $targetStatus = Enrichment::STATUS_WAITING_AI_ENRICHMENT;
        } elseif ($enrichmentCreationRequestPayload->getEnrichmentParameters()->getTranslateTo()) {
            $targetStatus = Enrichment::STATUS_WAITING_TRANSLATION;
        }

        $enrichment
            ->setStatus($targetStatus)
            ->setAiProcessedBy(null)
            ->setAiEnrichmentStartedAt(null)
            ->setAiEnrichmentEndedAt(null)
            ->setAiProcessingTaskId(null)
            ->setAiEvaluatedBy(null)
            ->setAiEvaluationStartedAt(null)
            ->setAiEvaluationEndedAt(null)
            ->setAiEvaluationTaskId(null)
            ->setTranslatedBy(null)
            ->setTranslationStartedAt(null)
            ->setTranslationStartedAt(null)
            ->setLatestEnrichmentRequestedAt(new DateTime())
            ->setEnrichmentRetries(0)
            ->setTranslationRetries(0)
            ->setEvaluationRetries(0)
            ->setNotificationWebhookUrl($enrichmentCreationRequestPayload->getNotificationWebhookUrl())
            ->setNotificationLevel($enrichmentCreationRequestPayload->getNotificationLevel())
            ->setDisciplines($enrichmentCreationRequestPayload->getEnrichmentParameters()->getDisciplines())
            ->setMediaTypes($enrichmentCreationRequestPayload->getEnrichmentParameters()->getMediaTypes())
            ->setAiEvaluation($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiEvaluation())
            ->setEndUserIdentifier($enrichmentCreationRequestPayload->getEndUserIdentifier())
            ->setAiModel($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiModel())
            ->setInfrastructure($enrichmentCreationRequestPayload->getEnrichmentParameters()->getInfrastructure())
            ->setLanguage($enrichmentCreationRequestPayload->getEnrichmentParameters()->getLanguage())
            ->setTranslateTo($enrichmentCreationRequestPayload->getEnrichmentParameters()->getTranslateTo())
            ->setGenerateMetadata($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateMetadata())
            ->setGenerateQuiz($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateQuiz())
            ->setGenerateNotes($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateNotes())
        ;

        $latestVersion = $enrichmentVersionRepository->findBy(['enrichment' => $enrichmentId], ['createdAt' => 'DESC'])[0];

        $newTranscript = (new Transcript())
                ->setText($latestVersion->getTranscript()->getText())
                ->setTranslatedText($latestVersion->getTranscript()->getTranslatedText())
                ->setLanguage($latestVersion->getTranscript()->getLanguage())
                ->setOriginalFilename($latestVersion->getTranscript()->getOriginalFilename())
                ->setSentences($latestVersion->getTranscript()->getSentences())
                ->setTranslatedSentences($latestVersion->getTranscript()->getTranslatedSentences())
        ;
        $enrichmentVersion = (new EnrichmentVersion())
            ->setAiGenerated(true)
            ->setTranscript($newTranscript)
            ->setNotificationWebhookUrl($enrichmentCreationRequestPayload->getNotificationWebhookUrl())
            ->setDisciplines($enrichmentCreationRequestPayload->getEnrichmentParameters()->getDisciplines())
            ->setMediaTypes($enrichmentCreationRequestPayload->getEnrichmentParameters()->getMediaTypes())
            ->setAiEvaluation($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiEvaluation())
            ->setEndUserIdentifier($enrichmentCreationRequestPayload->getEndUserIdentifier())
            ->setAiModel($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiModel())
            ->setInfrastructure($enrichmentCreationRequestPayload->getEnrichmentParameters()->getInfrastructure())
            ->setLanguage($enrichmentCreationRequestPayload->getEnrichmentParameters()->getLanguage())
            ->setTranslateTo($enrichmentCreationRequestPayload->getEnrichmentParameters()->getTranslateTo())
            ->setGenerateMetadata($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateMetadata())
            ->setGenerateQuiz($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateQuiz())
            ->setGenerateNotes($enrichmentCreationRequestPayload->getEnrichmentParameters()->getGenerateNotes())
        ;

        $enrichment->addVersion($enrichmentVersion)->setAiGenerationCount($enrichment->getAiGenerationCount() + 1);
        $enrichment->setLastEnrichmentVersion($enrichmentVersion);

        $entityManager->persist($enrichment);
        $entityManager->flush();

        return $this->json(['status' => 'OK']);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Evaluate an enrichment version',
        summary: 'Evaluate an enrichment version'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_version_evaluation']),
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Enrichment version evaluated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
                new OA\Property(
                    type: 'object',
                    ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript'])
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/evaluate', name: 'evaluate_enrichment_version', methods: ['POST'])]
    public function evaluateEnrichmentVersion(
        string $enrichmentId,
        string $versionId,
        #[MapRequestPayload(validationGroups: 'no_validation')] EnrichmentVersionUserEvaluationRequestPayload $enrichmentVersionUserEvaluationRequestPayload,
        EntityManagerInterface $entityManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
    ): Response {
        $multipleChoiceQuestion = null;
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }
        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess($enrichmentVersion, $versionId, $enrichmentId);
        if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

        if (!$enrichmentVersion->isAiGenerated()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'versionId',
                    'message' => 'You cannot evaluate an enrichment version that was not generated by AI',
                ],
            ]], 400);
        }

        if ($enrichmentVersion->getEnrichmentVersionMetadata() instanceof EnrichmentVersionMetadata && $enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata() instanceof EnrichmentVersionMetadata) {
            $enrichmentVersion->getEnrichmentVersionMetadata()
                ->setThumbUpTitle($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getThumbUpTitle())
                ->setThumbUpDescription($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getThumbUpDescription())
                ->setThumbUpDiscipline($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getThumbUpDiscipline())
                ->setThumbUpMediaType($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getThumbUpMediaType())
                ->setThumbUpTopics($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getThumbUpTopics())
                ->setUserFeedback($enrichmentVersionUserEvaluationRequestPayload->getEnrichmentVersionMetadata()->getUserFeedback())
            ;
        }

        foreach ($enrichmentVersion->getMultipleChoiceQuestions() as $multipleChoiceQuestion) {
            $evaluatedMultiutMultipleChoiceQuestion = $enrichmentVersionUserEvaluationRequestPayload->getMultipleChoiceQuestions()->findFirst(
                fn (int $index, MultipleChoiceQuestion $currentMultipleChoiceQuestion) => $multipleChoiceQuestion->getId()->equals($currentMultipleChoiceQuestion->getId())
            );
            if ($evaluatedMultiutMultipleChoiceQuestion instanceof MultipleChoiceQuestion) {
                $multipleChoiceQuestion
                    ->setThumbUp($evaluatedMultiutMultipleChoiceQuestion->getThumbUp())
                    ->setUserFeedback($evaluatedMultiutMultipleChoiceQuestion->getUserFeedback())
                ;
                foreach ($multipleChoiceQuestion->getChoices() as $choice) {
                    $evaluatedChoice = $evaluatedMultiutMultipleChoiceQuestion->getChoices()->findFirst(
                        fn (int $index, Choice $currentChoice) => $choice->getId()->equals($currentChoice->getId())
                    );

                    if ($evaluatedChoice instanceof Choice) {
                        $choice
                            ->setThumbUp($evaluatedChoice->getThumbUp())
                        ;
                    }
                }
            }
        }

        $enrichmentVersion->setLastEvaluationDate(new DateTime());
        $entityManager->flush();

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json(['status' => 'OK', 'enrichmentVersion' => $enrichmentVersion], context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Evaluate a multiple choice question',
        summary: 'Evaluate a multiple choice question'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'thumbUp',
                    type: 'boolean'
                ),
                new OA\Property(
                    property: 'userFeedback',
                    type: 'string'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Multiple choice question evaluated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
                new OA\Property(
                    type: 'object',
                    ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript'])
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/mcq/{mcqId}', name: 'evaluate_multiple_choice_question', methods: ['POST'])]
    public function evaluateMultipleChoiceQuestion(
        string $enrichmentId,
        string $versionId,
        string $mcqId,
        #[MapRequestPayload(validationGroups: 'no_validation')] MultipleChoiceQuestion $evaluatedMultipleChoiceQuestion,
        EntityManagerInterface $entityManager,
        MultipleChoiceQuestionRepository $multipleChoiceQuestionRepository,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($mcqId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $multipleChoiceQuestion = $multipleChoiceQuestionRepository->findOneBy(['id' => $mcqId]);

        $multipleChoiceQuestionAccessErrorResponse = $this->validateMultipleChoiceQuestionAccess($multipleChoiceQuestion, $mcqId, $versionId, $enrichmentId);
        if ($multipleChoiceQuestionAccessErrorResponse instanceof JsonResponse) {
            return $multipleChoiceQuestionAccessErrorResponse;
        }

        if (!$multipleChoiceQuestion->getEnrichmentVersion()->isAiGenerated()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'versionId',
                    'message' => 'You cannot evaluate a MCQ of an enrichment version that was not generated by AI',
                ],
            ]], 400);
        }

        $multipleChoiceQuestion
            ->setThumbUp($evaluatedMultipleChoiceQuestion->getThumbUp())
            ->setUserFeedback($evaluatedMultipleChoiceQuestion->getUserFeedback())
        ;

        $entityManager->flush();

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json(['status' => 'OK', 'enrichmentVersion' => $multipleChoiceQuestion->getEnrichmentVersion()], context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Evaluate a choice in a question',
        summary: 'Evaluate a choice in a question'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'thumbUp',
                    type: 'boolean'
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Choice evaluated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'OK',
                    type: 'string'
                ),
                new OA\Property(
                    type: 'object',
                    ref: new Model(type: EnrichmentVersion::class, groups: ['enrichment_versions', 'enrichment_versions_with_transcript'])
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/mcq/{mcqId}/choice/{choiceId}', name: 'evaluate_choice', methods: ['POST'])]
    public function evaluateChoice(
        string $enrichmentId,
        string $versionId,
        string $mcqId,
        string $choiceId,
        #[MapRequestPayload(validationGroups: 'no_validation')] Choice $evaluatedChoice,
        EntityManagerInterface $entityManager,
        ChoiceRepository $choiceRepository,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($mcqId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($choiceId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $choice = $choiceRepository->findOneBy(['id' => $choiceId]);

        $choiceAccessErrorResponse = $this->validateChoiceAccess(
            $choice, $choiceId, $mcqId, $versionId, $enrichmentId
        );
        if ($choiceAccessErrorResponse instanceof JsonResponse) {
            return $choiceAccessErrorResponse;
        }

        if (!$choice->getMultipleChoiceQuestion()->getEnrichmentVersion()->isAiGenerated()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'versionId',
                    'message' => 'You cannot evaluate a MCQ of an enrichment version that was not generated by AI',
                ],
            ]], 400);
        }

        $choice->setThumbUp($evaluatedChoice->getThumbUp());

        $entityManager->flush();

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json(['status' => 'OK', 'enrichmentVersion' => $choice->getMultipleChoiceQuestion()->getEnrichmentVersion()], context: $options);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Get(
        description: 'Download the transcript of an enrichment version',
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns transcript in the specified format',
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
    #[OA\Parameter(
        name: 'format',
        description: 'format (SRT/VTT)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'language',
        description: 'language',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/download_transcript', name: 'downlaod_transcript', methods: ['GET'])]
    public function downloadTranscript(
        string $enrichmentId,
        string $versionId,
        Request $request,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentUtils $enrichmentUtils,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($enrichmentId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess($enrichmentVersion, $versionId, $enrichmentId);
        if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

        $format = $request->query->get('format', 'srt');

        if (!in_array(strtolower($format), ['srt', 'vtt'])) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'format' => sprintf("'%s' is not a supported format. Supported formats : SRT, VTT", $format),
                ],
            ]], 400);
        }

        $language = $request->query->get('language');

        if (in_array($language, [$enrichmentVersion->getLanguage(), $enrichmentVersion->getTranscript()->getLanguage(), null])) {
            $pickTranslated = false;
        } elseif ($language === $enrichmentVersion->getTranslateTo()) {
            $pickTranslated = true;
        } else {
            return $this->json([
                'status' => 'KO',
                'errors' => [
                    [
                        'language' => sprintf(
                            "Couldn't find transcript in the specified language '%s'. "
                            .'Found languages : %s. You can also leave this parameter empty to get the default transcript.',
                            $language,
                            implode(',', array_unique(
                                array_filter(
                                    [$enrichmentVersion->getLanguage(), $enrichmentVersion->getTranslateTo(), $enrichmentVersion->getTranscript()->getLanguage()],
                                    fn ($value) => null !== $value
                                )
                            ))
                        ),
                    ],
                ]], 400);
        }

        $content = $enrichmentUtils->transcriptToSubtitles($enrichmentVersion->getTranscript(), $pickTranslated, $format);

        $tempFile = tempnam(sys_get_temp_dir(), 'transcript');
        file_put_contents($tempFile, $content);

        $binaryFileResponse = new BinaryFileResponse($tempFile);
        $binaryFileResponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            pathinfo((string) $enrichmentVersion->getEnrichment()->getMedia()->getOriginalFileName())['filename'].'.'.$format
        );

        $binaryFileResponse->deleteFileAfterSend(true);

        return $binaryFileResponse;
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new Uuid());

        if ($constraintViolationList->count() > 0) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("'%s' is not a valid UUID", $id),
                ],
            ]], 400);
        }

        return null;
    }

    private function validateEnrichmentAccess(?Enrichment $enrichment, string $enrichmentId): ?JsonResponse
    {
        if (!$enrichment instanceof Enrichment) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("No enrichment with ID '%s' has been found", $enrichmentId),
                ],
            ]], 404);
        }

        if ($enrichment->isDeleted()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("The enrichment that you want to get '%s' has been deleted", $enrichmentId),
                ],
            ]], 404);
        }

        if ($enrichment->getCreatedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => sprintf('You are not allowed to access enrichment %s', $enrichmentId),
                ],
            ]], 403);
        }

        return null;
    }

    private function validateEnrichmentVersionAccess(?EnrichmentVersion $enrichmentVersion, string $enrichmentVersionId, string $enrichmentId): ?JsonResponse
    {
        $enrichment = $enrichmentVersion instanceof EnrichmentVersion ? $enrichmentVersion->getEnrichment() : null;

        if ($enrichment && $enrichment->getId()->toRfc4122() === $enrichmentId) {
            return $this->validateEnrichmentAccess($enrichment, $enrichmentId);
        } else {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf('The enrichment version %s has not been found or the enrichment id %s in the url is incorrect', $enrichmentVersionId, $enrichmentId),
                ],
            ]], 403);
        }
    }

    private function validateMultipleChoiceQuestionAccess(?MultipleChoiceQuestion $multipleChoiceQuestion, string $mcqId, string $enrichmentVersionId, string $enrichmentId): ?JsonResponse
    {
        $enrichment = $multipleChoiceQuestion instanceof MultipleChoiceQuestion ?
            (
                $multipleChoiceQuestion->getEnrichmentVersion() instanceof EnrichmentVersion ? $multipleChoiceQuestion->getEnrichmentVersion()->getEnrichment() : null
            )
            : null;

        if ($enrichment && $enrichment->getId()->toRfc4122() === $enrichmentId && $multipleChoiceQuestion->getEnrichmentVersion()->getId()->toRfc4122() === $enrichmentVersionId) {
            return $this->validateEnrichmentAccess($enrichment, $enrichmentId);
        } else {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf('The multiple choice question %s has not been found or one of the enrichment version id %s or enrichment id %s in the url is incorrect', $mcqId, $enrichmentVersionId, $enrichmentId),
                ],
            ]], 403);
        }
    }

    private function validateChoiceAccess(?Choice $choice, string $choiceId, string $mcqId, string $enrichmentVersionId, string $enrichmentId): ?JsonResponse
    {
        $enrichment = $choice instanceof Choice ?
            (
                $choice->getMultipleChoiceQuestion() instanceof MultipleChoiceQuestion ? $choice->getMultipleChoiceQuestion()->getEnrichmentVersion() instanceof EnrichmentVersion ? $choice->getMultipleChoiceQuestion()->getEnrichmentVersion()->getEnrichment() : null : null
            )
            : null;

        if (
            $enrichment
            && $enrichment->getId()->toRfc4122() === $enrichmentId
            && $choice->getMultipleChoiceQuestion()->getId()->toRfc4122() === $mcqId
            && $choice->getMultipleChoiceQuestion()->getEnrichmentVersion()->getId()->toRfc4122() === $enrichmentVersionId
        ) {
            return $this->validateEnrichmentAccess($enrichment, $enrichmentId);
        } else {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf('The choice %s has not been found or one of the multiple choice question %s or the enrichment version id %s or enrichment id %s in the url is incorrect',
                        $choiceId, $mcqId, $enrichmentVersionId, $enrichmentId
                    ),
                ],
            ]], 403);
        }
    }
}
