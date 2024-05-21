<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\AnswerPointer;
use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\EnrichmentVersionMetadata;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Exception\UploadFileUnsupportedTypeException;
use App\Message\FileUploadFromUrlMessage;
use App\Model\EnrichmentCreationFileUploadRequestPayload;
use App\Model\EnrichmentCreationRequestPayload;
use App\Model\EnrichmentCreationUrlRequestPayload;
use App\Model\EnrichmentParameters;
use App\Model\EnrichmentVersionCreationRequestPayload;
use App\Model\ErrorsResponse;
use App\Repository\ApiClientRepository;
use App\Repository\ChoiceRepository;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Repository\MultipleChoiceQuestionRepository;
use App\Service\ApiClientManager;
use App\Service\FileUploadService;
use App\Service\ScopeAuthorizationCheckerService;
use App\Utils\PaginationUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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
        private readonly PaginationUtils $paginationUtils,
        private readonly ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService
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
    public function getEnrichments(Request $request, ApiClientManager $apiClientManager, EnrichmentRepository $enrichmentRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $sort = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'DESC');
        $size = $request->query->get('size', 50);
        $page = $request->query->get('page', 1);
        $endUserIdentifier = $request->query->get('endUserIdentifier');
        $withStatus = $request->query->get('withStatus', 'false');

        $groups = ['enrichments'];

        if ('true' === $withStatus) {
            $groups[] = 'enrichments_with_status';
        }

        $paginationParametersErrors = $this->paginationUtils->paginationRequestParametersValidator(Enrichment::getSortFields(), $sort, $order, $size, $page);

        if ([] !== $paginationParametersErrors) {
            return $this->json(['status' => 'KO', 'errors' => $paginationParametersErrors], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $enrichments = $enrichmentRepository->findByCreatedBy($clientEntity->getIdentifier(), $page, $size, $sort, $order, $endUserIdentifier);

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
    public function getAiModelInfrastructureCombinations(ApiClientRepository $apiClientRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $cominations = $apiClientRepository->getDistinctCombinations();

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
    public function getEnrichmentByID(string $id, ApiClientManager $apiClientManager, EnrichmentRepository $enrichmentRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            AbstractNormalizer::GROUPS => ['enrichments'],
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
    public function deleteEnrichmentByID(string $id, ApiClientManager $apiClientManager, EnrichmentRepository $enrichmentRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to delete this resource']], 403);
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

        $entityManager->remove($enrichment);
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
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($id);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
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

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $enrichmentVersions = $enrichmentVersionRepository->findByEnrichmentId($id, $page, $size, $sort, $order);

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
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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

        $initialVersion = 0 === $enrichment->getVersions()->count();

        if ($initialVersion) {
            return $this->json([
                'status' => 'KO',
                'errors' => ['No initial version found for the enrichment. Please wait for the generation of the initial version before pushing a new one'],
            ], 403);
        }

        $inputTranscript = $request->files->get('transcript');

        if (null !== $inputTranscript) {
            $transcriptContent = json_decode((string) $inputTranscript->getContent(), true, 512, JSON_THROW_ON_ERROR);
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

        $inputEnrichmentVersionMetadata = json_decode($request->request->get('enrichmentVersionMetadata'), true, 512, JSON_THROW_ON_ERROR);
        $inputMultipleChoiceQuestions = $this->stringJsonObjectsToArray($request->request->get('multipleChoiceQuestions'));

        $enrichmentVersionMetadata = (new EnrichmentVersionMetadata())
            ->setDescription($inputEnrichmentVersionMetadata['description'])
            ->setTitle($inputEnrichmentVersionMetadata['title'])
            ->setDiscipline($inputEnrichmentVersionMetadata['discipline'])
            ->setMediaType($inputEnrichmentVersionMetadata['mediaType'])
            ->setTopics($inputEnrichmentVersionMetadata['topics'])
        ;

        $enrichmentVersion = (new EnrichmentVersion())
            ->setTranscript($newTranscript)
            ->setEnrichmentVersionMetadata($enrichmentVersionMetadata)
        ;

        foreach ($inputMultipleChoiceQuestions as $inputMultipleChoiceQuestion) {
            $multipleChoiceQuestion = (new MultipleChoiceQuestion())
                ->setQuestion($inputMultipleChoiceQuestion['question'])
                ->setExplanation($inputMultipleChoiceQuestion['explanation'])
            ;
            $answerPointer = $inputMultipleChoiceQuestion['answerPointer'];
            if ($answerPointer && $answerPointer['startAnswerPointer']) {
                $anwserPointerEntity = (new AnswerPointer())->setStartAnswerPointer($answerPointer['startAnswerPointer']);
                if ($answerPointer['stopAnswerPointer']) {
                    $anwserPointerEntity->setStopAnswerPointer($answerPointer['stopAnswerPointer']);
                }
                $multipleChoiceQuestion->setAnswerPointer($anwserPointerEntity);
            }

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
    public function getLatestEnrichmentVersionByEnrichmentID(string $id, EnrichmentVersionRepository $enrichmentVersionRepository, EnrichmentRepository $enrichmentRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}', name: 'enrichment_version', methods: ['GET'])]
    public function getEnrichmentVersionByID(string $enrichmentId, string $versionId, EnrichmentRepository $enrichmentRepository, EnrichmentVersionRepository $enrichmentVersionRepository): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            return $this->json(['status' => 'KO', 'errors' => "Can't delete initial version"], 403);
        }

        $entityManager->remove($enrichmentVersion);
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
            ref: new Model(type: EnrichmentCreationUrlRequestPayload::class),
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
    public function createEnrichmentFromUrl(Request $request, FileUploadService $fileUploadService, MessageBusInterface $messageBus, ApiClientManager $apiClientManager, EntityManagerInterface $entityManager): Response
    {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $enrichmentCreationUrlRequestPayload = (new EnrichmentCreationUrlRequestPayload())
            ->setUrl($content['url'])
            ->setEndUserIdentifier(null === $content['endUserIdentifier'] || '' === $content['endUserIdentifier'] ? null : $content['endUserIdentifier'])
            ->setNotificationWebhookUrl($content['notificationWebhookUrl'])
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($content['enrichmentParameters']['disciplines'] ?? [])
                ->setMediaTypes($content['enrichmentParameters']['mediaTypes'] ?? [])
                ->setAiEvaluation($content['enrichmentParameters']['aiEvaluation'] ?? null)
                ->setAiModel(null === $content['enrichmentParameters']['aiModel'] || '' === $content['enrichmentParameters']['aiModel'] ? null : $content['enrichmentParameters']['aiModel'])
                ->setInfrastructure(null === $content['enrichmentParameters']['infrastructure'] || '' === $content['enrichmentParameters']['infrastructure'] ? null : $content['enrichmentParameters']['infrastructure'])
            )
        ;

        $errors = $this->validator->validate($enrichmentCreationUrlRequestPayload);
        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $enrichment = (new Enrichment())
            ->setStatus(Enrichment::STATUS_WAITING_MEIDA_UPLOAD)
            ->setCreatedBy($clientEntity)
            ->setMediaUrl($enrichmentCreationUrlRequestPayload->getUrl())
            ->setNotificationWebhookUrl($enrichmentCreationUrlRequestPayload->getNotificationWebhookUrl())
            ->setDisciplines($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getDisciplines())
            ->setMediaTypes($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getMediaTypes())
            ->setAiEvaluation($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getAiEvaluation())
            ->setEndUserIdentifier($enrichmentCreationUrlRequestPayload->getEndUserIdentifier())
            ->setAiModel($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getAiModel())
            ->setInfrastructure($enrichmentCreationUrlRequestPayload->getEnrichmentParameters()->getInfrastructure())
        ;

        $entityManager->persist($enrichment);
        $entityManager->flush();

        $messageBus->dispatch(new FileUploadFromUrlMessage($enrichment->getId(), $clientEntity, $enrichmentCreationUrlRequestPayload));

        return $this->json(['status' => 'OK', 'id' => $enrichment->getId()]);
    }

    #[OA\Tag(name: 'Enrichments')]
    #[OA\Post(
        description: 'Create an enrichment from a file',
        summary: 'Create an enrichment from a file'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: EnrichmentCreationFileUploadRequestPayload::class))
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
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        $originalFileName = $request->request->get('originalFileName');
        if ($originalFileName) {
            $file = new UploadedFile($file->getPathname(), $originalFileName, $file->getMimeType());
        }

        $inputEnrichmentParameters = json_decode($request->request->get('enrichmentParameters'), true, 512, JSON_THROW_ON_ERROR);
        $endUserIdentifier = $request->request->get('endUserIdentifier');
        $aiModel = $inputEnrichmentParameters['aiModel'] ?? null;
        $infrastructure = $inputEnrichmentParameters['infrastructure'] ?? null;
        $enrichmentCreationFileUploadRequestPayload = (new EnrichmentCreationFileUploadRequestPayload())
            ->setFile($file)
            ->setNotificationWebhookUrl($request->request->get('notificationWebhookUrl'))
            ->setEndUserIdentifier(null === $endUserIdentifier || '' === $endUserIdentifier ? null : $endUserIdentifier)
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($inputEnrichmentParameters['disciplines'] ?? [])
                ->setMediaTypes($inputEnrichmentParameters['mediaTypes'] ?? [])
                ->setAiEvaluation($inputEnrichmentParameters['aiEvaluation'] ?? null)
                ->setAiModel(null === $aiModel || '' === $aiModel ? null : $aiModel)
                ->setInfrastructure(null === $infrastructure || '' === $infrastructure ? null : $infrastructure)
            )
        ;

        $errors = $this->validator->validate($enrichmentCreationFileUploadRequestPayload);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $enrichment = (new Enrichment())
                ->setCreatedBy($clientEntity)
                ->setNotificationWebhookUrl($enrichmentCreationFileUploadRequestPayload->getNotificationWebhookUrl())
                ->setDisciplines($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getDisciplines())
                ->setMediaTypes($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getMediaTypes())
                ->setAiEvaluation($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getAiEvaluation())
                ->setEndUserIdentifier($enrichmentCreationFileUploadRequestPayload->getEndUserIdentifier())
                ->setAiModel($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getAiModel())
                ->setInfrastructure($enrichmentCreationFileUploadRequestPayload->getEnrichmentParameters()->getInfrastructure())
        ;

        try {
            $enrichment = $fileUploadService->uploadFile($file, $clientEntity, $enrichment);
        } catch (UploadFileUnsupportedTypeException $exception) {
            return $this->json([
                'status' => 'KO',
                'errors' => [$exception->getMessage()],
            ], 400);
        }

        $errors = $this->validator->validate($enrichment);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'KO',
                'errors' => array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors)),
            ], 400);
        }
        $entityManager->persist($enrichment);
        $entityManager->flush();

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
            ref: new Model(type: EnrichmentCreationRequestPayload::class),
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
        Request $request,
        ApiClientManager $apiClientManager,
        EntityManagerInterface $entityManager,
        EnrichmentRepository $enrichmentRepository,
        EnrichmentVersionRepository $enrichmentVersionRepository
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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

        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $enrichmentCreationRequestPayload = (new EnrichmentCreationRequestPayload())
            ->setEndUserIdentifier(null === $content['endUserIdentifier'] || '' === $content['endUserIdentifier'] ? null : $content['endUserIdentifier'])
            ->setNotificationWebhookUrl($content['notificationWebhookUrl'])
            ->setEnrichmentParameters((new EnrichmentParameters())
                ->setDisciplines($content['enrichmentParameters']['disciplines'] ?? [])
                ->setMediaTypes($content['enrichmentParameters']['mediaTypes'] ?? [])
                ->setAiEvaluation($content['enrichmentParameters']['aiEvaluation'] ?? null)
                ->setAiModel(null === $content['enrichmentParameters']['aiModel'] || '' === $content['enrichmentParameters']['aiModel'] ? null : $content['enrichmentParameters']['aiModel'])
                ->setInfrastructure(null === $content['enrichmentParameters']['infrastructure'] || '' === $content['enrichmentParameters']['infrastructure'] ? null : $content['enrichmentParameters']['infrastructure'])
            )
        ;

        $errors = $this->validator->validate($enrichmentCreationRequestPayload);
        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }

        $enrichment
            ->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)
            ->setAiProcessedBy(null)
            ->setAiEnrichmentStartedAt(null)
            ->setAiEnrichmentEndedAt(null)
            ->setAiProcessingTaskId(null)
            ->setAiEvaluatedBy(null)
            ->setAiEvaluationStartedAt(null)
            ->setAiEvaluationEndedAt(null)
            ->setAiEvaluationTaskId(null)
            ->setNotificationWebhookUrl($enrichmentCreationRequestPayload->getNotificationWebhookUrl())
            ->setDisciplines($enrichmentCreationRequestPayload->getEnrichmentParameters()->getDisciplines())
            ->setMediaTypes($enrichmentCreationRequestPayload->getEnrichmentParameters()->getMediaTypes())
            ->setAiEvaluation($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiEvaluation())
            ->setEndUserIdentifier($enrichmentCreationRequestPayload->getEndUserIdentifier())
            ->setAiModel($enrichmentCreationRequestPayload->getEnrichmentParameters()->getAiModel())
            ->setInfrastructure($enrichmentCreationRequestPayload->getEnrichmentParameters()->getInfrastructure())
        ;

        $initialVersion = $enrichmentVersionRepository->findOneBy(['id' => $enrichment->getInitialVersionId()]);
        $newTranscript = (new Transcript())
                ->setText($initialVersion->getTranscript()->getText())
                ->setLanguage($initialVersion->getTranscript()->getLanguage())
                ->setOriginalFilename($initialVersion->getTranscript()->getOriginalFilename())
                ->setSentences($initialVersion->getTranscript()->getSentences())
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
        ;

        $enrichment->addVersion($enrichmentVersion);

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
        Request $request,
        EntityManagerInterface $entityManager,
        EnrichmentVersionRepository $enrichmentVersionRepository
    ): Response {
        $multipleChoiceQuestion = null;
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            return $this->json(['status' => 'KO', 'errors' => ['You cannot evaluate an enrichment version that was not generated by AI']], 400);
        }

        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $enrichmentVersion->getEnrichmentVersionMetadata()
            ->setThumbUpTitle($content['enrichmentVersionMetadata']['thumbUpTitle'])
            ->setThumbUpDescription($content['enrichmentVersionMetadata']['thumbUpDescription'])
            ->setThumbUpDiscipline($content['enrichmentVersionMetadata']['thumbUpDiscipline'])
            ->setThumbUpMediaType($content['enrichmentVersionMetadata']['thumbUpMediaType'])
            ->setThumbUpTopics($content['enrichmentVersionMetadata']['thumbUpTopics'])
            ->setUserFeedback($content['enrichmentVersionMetadata']['userFeedback'])
        ;

        foreach ($enrichmentVersion->getMultipleChoiceQuestions() as $multipleChoiceQuestion) {
            $inputMultipleChoiceQuestion = array_values(array_filter($content['multipleChoiceQuestions'], fn (array $currentMultipleChoiceQuestion) => $multipleChoiceQuestion->getId()->toRfc4122() === $currentMultipleChoiceQuestion['id']));

            if ([] !== $inputMultipleChoiceQuestion) {
                $multipleChoiceQuestion
                    ->setThumbUp($inputMultipleChoiceQuestion[0]['thumbUp'])
                    ->setUserFeedback($inputMultipleChoiceQuestion[0]['userFeedback'])
                ;
                foreach ($multipleChoiceQuestion->getChoices() as $choice) {
                    $inputChoice = array_values(array_filter($inputMultipleChoiceQuestion[0]['choices'], fn (array $currentChoice) => $choice->getId()->toRfc4122() === $currentChoice['id']));

                    if ([] !== $inputChoice) {
                        $choice
                            ->setThumbUp($inputChoice[0]['thumbUp'])
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

        return $this->json(['status' => 'OK', 'enrichmentVersion' => $multipleChoiceQuestion->getEnrichmentVersion()], context: $options);
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
        Request $request,
        EntityManagerInterface $entityManager,
        MultipleChoiceQuestionRepository $multipleChoiceQuestionRepository
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            return $this->json(['status' => 'KO', 'errors' => ['You cannot evaluate a MCQ of an enrichment version that was not generated by AI']], 400);
        }

        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $multipleChoiceQuestion->setThumbUp($content['thumbUp'])->setUserFeedback($content['userFeedback']);

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
        Request $request,
        EntityManagerInterface $entityManager,
        ChoiceRepository $choiceRepository
    ): Response {
        if (!$this->scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_CLIENT)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            return $this->json(['status' => 'KO', 'errors' => ['You cannot evaluate a MCQ of an enrichment version that was not generated by AI']], 400);
        }

        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $choice->setThumbUp($content['thumbUp']);

        $entityManager->flush();

        $options = [
            AbstractNormalizer::GROUPS => ['enrichment_versions', 'enrichment_versions_with_transcript'],
        ];

        return $this->json(['status' => 'OK', 'enrichmentVersion' => $choice->getMultipleChoiceQuestion()->getEnrichmentVersion()], context: $options);
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new Uuid());

        if ($constraintViolationList->count() > 0) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("'%s' is not a valid UUID", $id)]], 400);
        }

        return null;
    }

    private function validateEnrichmentAccess(?Enrichment $enrichment, string $enrichmentId): ?JsonResponse
    {
        if (!$enrichment instanceof Enrichment) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No enrichment with ID '%s' has been found", $enrichmentId)]], 404);
        }

        if ($enrichment->getCreatedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf('You are not allowed to access enrichment %s', $enrichmentId)]], 403);
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
                sprintf('The enrichment version %s has not been found or the enrichment id %s in the url is incorrect', $enrichmentVersionId, $enrichmentId),
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
                sprintf('The multiple choice question %s has not been found or one of the enrichment version id %s or enrichment id %s in the url is incorrect', $mcqId, $enrichmentVersionId, $enrichmentId),
            ]], 403);
        }
    }

    private function validateChoiceAccess(?Choice $choice, string $choiceId, string $mcqId, string $enrichmentVersionId, string $enrichmentId): ?JsonResponse
    {
        $enrichment = $choice instanceof Choice ?
            (
                $choice->getMultipleChoiceQuestion() instanceof MultipleChoiceQuestion ? $choice->getMultipleChoiceQuestion()->getEnrichmentVersion() ? $choice->getMultipleChoiceQuestion()->getEnrichmentVersion()->getEnrichment() : null : null
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
                sprintf('The choice %s has not been found or one of the multiple choice question %s or the enrichment version id %s or enrichment id %s in the url is incorrect',
                    $choiceId, $mcqId, $enrichmentVersionId, $enrichmentId
                ),
            ]], 403);
        }
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
