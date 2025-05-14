<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\MultipleChoiceQuestion;
use App\Form\AiEvaluationRequestPayloadType;
use App\Model\AiEvaluationJobResponse;
use App\Model\AiEvaluationRequestPayload;
use App\Model\ErrorsResponse;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Service\ApiClientManager;
use App\Service\ScopeAuthorizationCheckerService;
use App\Utils\EnrichmentUtils;
use App\Utils\PaginationUtils;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/v1')]
class AiEvaluationWorkerController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils,
        private readonly EnrichmentUtils $enrichmentUtils,
    ) {
    }

    #[OA\Tag(name: 'AI Evaluation - Worker')]
    #[OA\Post(
        description: 'Evaluates the MCQs of an initial version of an enrichment with AI',
        summary: 'Evaluates the MCQs of an initial version of an enrichment with AI'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: AiEvaluationRequestPayload::class),
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Completed the initial version successfully',
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
        name: 'versionId',
        description: 'Enrichment version ID',
        in: 'path',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/ai_evaluation', name: 'evaluate_initial_enrichment_version', methods: ['POST'])]
    public function evaluateInitialEnrichmentVersionsByEnrichmentVersionID(
        string $enrichmentId,
        string $versionId,
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        HttpClientInterface $httpClient,
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_EVALUATION_WORKER)) {
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
        $aiEvaluationRequestPayload = new AiEvaluationRequestPayload();
        $form = $this->createForm(AiEvaluationRequestPayloadType::class, $aiEvaluationRequestPayload);
        $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $form->submit($requestBody);

        if ($form->isValid()) {
            $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

            $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess(
                $enrichmentVersion,
                $versionId,
                $enrichmentId,
                $aiEvaluationRequestPayload->getTaskId()
            );
            if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
                return $enrichmentVersionAccessErrorResponse;
            }

            $latestEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichmentId);
            if (!$enrichmentVersion->getId()->equals($latestEnrichmentVersion->getId()) || !$enrichmentVersion->isAiGenerated()) {
                return $this->json([
                    'status' => 'KO',
                    'errors' => [
                        [
                            'path' => 'versionId',
                            'message' => 'This enrichment version is not a placeholder AI generated version waiting for AI Evaluation',
                        ],
                    ],
                ], 403);
            }

            $enrichment = $enrichmentVersion->getEnrichment();

            if ('KO' === $aiEvaluationRequestPayload->getStatus()) {
                $enrichment->setFailureCause($aiEvaluationRequestPayload->getFailureCause());
                $enrichment->setStatus(Enrichment::STATUS_FAILURE);
                $enrichment->getAiEvaluatedBy()->setJobLastFailuredAt(new DateTime());
                $enrichmentVersion->setFailureCause($aiEvaluationRequestPayload->getFailureCause());
                $entityManager->flush();
                $this->enrichmentUtils->sendNotification($enrichment);

                return $this->json(['status' => 'OK']);
            }

            $evaluations = $aiEvaluationRequestPayload->getEvaluations();
            foreach ($evaluations as $evaluation) {
                $mcq = $enrichmentVersion->getMultipleChoiceQuestions()->findFirst(fn (int $index, MultipleChoiceQuestion $multipleChoiceQuestion) => $multipleChoiceQuestion->getId()->equals($evaluation->getId()));
                if ($mcq instanceof MultipleChoiceQuestion) {
                    $mcq->setEvaluation($evaluation->getEvaluation());
                }
            }
            $enrichment->setStatus(Enrichment::STATUS_SUCCESS)->setAiEvaluationEndedAt(new DateTime());
            $enrichmentVersion->setAiEvaluationEndedAt(new DateTime());

            $errors = $this->validator->validate($enrichmentVersion);
            if (count($errors) > 0) {
                $errorsArray = array_map(fn (ConstraintViolation $error) => [
                    'message' => $error->getMessage(),
                    'path' => $error->getPropertyPath(),
                ], iterator_to_array($errors));

                return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
            }
            $entityManager->flush();
            $this->enrichmentUtils->sendNotification($enrichment);

            $enrichment->getAiEvaluatedBy()->setJobLastSuccessAt(new DateTime());
            $entityManager->flush();

            return $this->json(['status' => 'OK']);
        } else {
            $errorsArray = array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getOrigin()->getName(),
            ], iterator_to_array($form->getErrors(deep: true)));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }
    }

    #[OA\Tag(name: 'AI Evaluation - Worker')]
    #[OA\Get(
        description: 'Get an evaluation job',
        summary: 'Get an evaluation job'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a job',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: AiEvaluationJobResponse::class),
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
        description: 'No job has been found for now',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[OA\Parameter(
        name: 'taskId',
        description: 'Task ID',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'evaluator',
        description: 'The name of the evaluator',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/enrichments/job/ai_evaluation/oldest', name: 'get_evaluation_job', methods: ['GET'])]
    public function getAiEvaluationJob(
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        EnrichmentVersionRepository $enrichmentVersionRepository,
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_EVALUATION_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $taskId = $request->query->get('taskId');
        $evaluator = $request->query->get('evaluator');

        $uuidValidationErrorResponse = $this->validateUuid($taskId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);
        $clientEntity->setJobLastRequestedAt(new DateTime());
        $entityManager->flush();

        $retryTimes = 2;
        for ($i = 0; $i < $retryTimes; ++$i) {
            $enrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEvaluationStatusOrAiEvaluatingStatusForMoreThanXMinutesByEvaluator($evaluator);

            if (!$enrichment instanceof Enrichment) {
                return $this->json(['status' => 'KO', 'errors' => [
                    [
                        'path' => null,
                        'message' => 'No job currently available',
                    ],
                ]], 404);
            }

            $latestEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichment->getId());

            if (!$latestEnrichmentVersion->isAiGenerated()) {
                return $this->json(['status' => 'KO', 'errors' => [
                    [
                        'path' => null,
                        'message' => sprintf('No enrichment version prepared for the eligible enrichment (%s), please report this issue', $enrichment->getId()),
                    ],
                ]], 404);
            }

            $enrichmentLock = $lockFactory->createLock(sprintf('evaluating-enrichment-%s', $enrichment->getId()));
            if ($enrichmentLock->acquire()) {
                if ($enrichment->getAiEvaluationEndedAt() instanceof DateTimeInterface) {
                    $enrichment->setEvaluationRetries($enrichment->getEvaluationRetries() + 1);
                    $latestEnrichmentVersion->setEvaluationRetries($enrichment->getEvaluationRetries() + 1);
                }

                $enrichment
                    ->setStatus(Enrichment::STATUS_AI_EVALUATING)
                    ->setAiEvaluationStartedAt(new DateTime())
                    ->setAiEvaluatedBy($clientEntity)
                    ->setAiEvaluationTaskId(Uuid::fromString($taskId))
                ;

                $latestEnrichmentVersion
                    ->setAiEvaluationStartedAt(new DateTime())
                    ->setAiEvaluatedBy($clientEntity)
                ;

                $clientEntity->setJobLastTakendAt(new DateTime());

                $entityManager->flush();
                $enrichmentLock->release();

                $options = [
                    AbstractNormalizer::GROUPS => ['ai_evaluation_job'],
                ];

                $aiEvaluationJobResponse = (new AiEvaluationJobResponse())
                    ->setEnrichmentId($enrichment->getId())
                    ->setEnrichmentVersionId($latestEnrichmentVersion->getId())
                    ->setTranscript($latestEnrichmentVersion->getTranscript())
                    ->setMultipleChoiceQuestions($latestEnrichmentVersion->getMultipleChoiceQuestions())
                    ->setEnrichmentVersionMetadata($latestEnrichmentVersion->getEnrichmentVersionMetadata())
                ;

                return $this->json($aiEvaluationJobResponse, context: $options);
            }
        }

        return $this->json(['status' => 'KO', 'errors' => [
            [
                'path' => null,
                'message' => 'No job currently available',
            ],
        ]], 404);
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new UuidConstraint());

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

    private function validateEnrichmentVersionAccess(?EnrichmentVersion $enrichmentVersion, string $enrichmentVersionId, string $enrichmentId, string $taskId): ?JsonResponse
    {
        if (!$enrichmentVersion instanceof EnrichmentVersion) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'versionId',
                    'message' => sprintf("No enrichment version with ID '%s' has been found", $enrichmentVersionId),
                ],
            ]], 404);
        }

        $enrichment = $enrichmentVersion->getEnrichment();

        if ($enrichment->getId()->toRfc4122() !== $enrichmentId) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'enrichmentId',
                    'message' => sprintf('The enrichment id %s in the url is incorrect', $enrichmentId),
                ],
            ]], 403);
        }

        if (
            $enrichment->getAiEvaluatedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')
            || (string) $enrichment->getAiEvaluationTaskId() !== $taskId
        ) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'You are not allowed to access this enrichment version',
                ],
            ]], 403);
        }

        return null;
    }
}
