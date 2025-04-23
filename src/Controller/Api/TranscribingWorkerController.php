<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\Transcript;
use App\Model\EnrichmentTranscriptRequestPayload;
use App\Model\ErrorsResponse;
use App\Model\TranscriptionJobResponse;
use App\Repository\EnrichmentRepository;
use App\Service\ApiClientManager;
use App\Service\FileUploadService;
use App\Service\ScopeAuthorizationCheckerService;
use App\Utils\EnrichmentUtils;
use App\Utils\PaginationUtils;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/v1')]
class TranscribingWorkerController extends AbstractController
{
    public function __construct(
        private readonly bool $autoDeleteMediaAfterTranscription,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils,
        private readonly EnrichmentUtils $enrichmentUtils,
    ) {
    }

    #[OA\Tag(name: 'Transcribing Media - Worker')]
    #[OA\Post(
        description: 'Create an initial version of an enrichment',
        summary: 'Create a new version of an enrichment'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: EnrichmentTranscriptRequestPayload::class))
            ),
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Created an initial version successfully',
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
    #[Route('/enrichments/{id}/versions/initial/transcript', name: 'create_initial_enrichment_version_with_transcript', methods: ['POST'])]
    public function createInitialEnrichmentVersionsByEnrichmentID(
        string $id,
        Request $request,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        FilesystemOperator $mediaStorage,
        HttpClientInterface $httpClient,
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSCRIPTION_WORKER)) {
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

        $taskId = $request->request->get('taskId');
        $status = $request->request->get('status');
        $failureCause = $request->request->get('failureCause');

        $enrichment = $enrichmentRepository->findOneBy(['id' => $id]);

        $enrichmentAccessErrorResponse = $this->validateEnrichmentAccess($enrichment, $id, $taskId);
        if ($enrichmentAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentAccessErrorResponse;
        }

        $initialVersion = 0 === $enrichment->getVersions()->count();

        if (!$initialVersion) {
            return $this->json([
                'status' => 'KO',
                'errors' => [
                    [
                        'path' => 'id',
                        'message' => 'There is already an initial version found for the enrichment',
                    ],
                ],
            ], 403);
        }

        if ('KO' === $status) {
            $enrichment->setFailureCause($failureCause);
            $enrichment->setStatus(Enrichment::STATUS_FAILURE);
            $enrichment->getTranscribedBy()->setJobLastFailuredAt(new DateTime());
            $entityManager->flush();
            $this->enrichmentUtils->sendNotification($enrichment);

            return $this->json(['status' => 'OK']);
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
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'transcript',
                    'message' => 'No transcript has been given',
                ],
            ]], 400);
        }

        $enrichmentVersion = (new EnrichmentVersion())
            ->setInitialVersion(true)
            ->setAiGenerated(true)
            ->setTranscript($newTranscript)
            ->setNotificationWebhookUrl($enrichment->getNotificationWebhookUrl())
            ->setDisciplines($enrichment->getDisciplines())
            ->setMediaTypes($enrichment->getMediaTypes())
            ->setAiEvaluation($enrichment->getAiEvaluation())
            ->setEndUserIdentifier($enrichment->getEndUserIdentifier())
            ->setAiModel($enrichment->getAiModel())
            ->setInfrastructure($enrichment->getInfrastructure())
            ->setLanguage($enrichment->getLanguage())
            ->setTranslateTo($enrichment->getTranslateTo())
        ;

        $enrichment->addVersion($enrichmentVersion)->setAiGenerationCount(1);

        $targetStatus = Enrichment::STATUS_SUCCESS;
        if ($enrichment->getGenerateMetadata() || $enrichment->getGenerateQuiz() || $enrichment->getGenerateNotes()) {
            $targetStatus = Enrichment::STATUS_WAITING_AI_ENRICHMENT;
        } elseif ($enrichment->getTranslateTo()) {
            $targetStatus = Enrichment::STATUS_WAITING_TRANSLATION;
        } elseif ($enrichment->getAiEvaluation()) {
            $targetStatus = Enrichment::STATUS_WAITING_AI_EVALUATION;
        }

        $enrichment->setStatus($targetStatus)->setTransribingEndedAt(new DateTime());

        $errors = $this->validator->validate($enrichment, groups: ['Default']);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn (ConstraintViolation $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getPropertyPath(),
            ], iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }
        $enrichment->getTranscribedBy()->setJobLastSuccessAt(new DateTime());
        $entityManager->persist($enrichment);
        $entityManager->flush();

        if ($this->autoDeleteMediaAfterTranscription) {
            $mediaStorage->delete($enrichment->getMedia()->getFileDirectory().'/'.$enrichment->getMedia()->getFileName());
        }

        if (Enrichment::STATUS_SUCCESS === $targetStatus) {
            $this->enrichmentUtils->sendNotification($enrichment);
        }

        return $this->json(['status' => 'OK', 'id' => $enrichmentVersion->getId()]);
    }

    #[OA\Tag(name: 'Transcribing Media - Worker')]
    #[OA\Get(
        description: 'Get a transcription job',
        summary: 'Get a transcription job'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns an enrichment',
        content: new OA\JsonContent(
            ref: new Model(type: TranscriptionJobResponse::class),
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
    #[Route('/enrichments/transcription/job/oldest', name: 'get_transcription_job', methods: ['GET'])]
    public function getEnrichmentJob(
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        FileUploadService $fileUploadService,
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSCRIPTION_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'User not authorized to access this resource',
                ],
            ]], 403);
        }

        $taskId = $request->query->get('taskId');

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
            $enrichment = $enrichmentRepository->findOldestEnrichmentInWaitingMediaTranscriptionStatusOrTranscribingMediaStatusForMoreThanXMinutes(
                $clientEntity->getAiModel(),
                $clientEntity->getInfrastructure(),
                $clientEntity->getTreatUnspecifiedModelOrInfrastructure()
            );

            if (!$enrichment instanceof Enrichment) {
                return $this->json(['status' => 'KO', 'errors' => [
                    [
                        'path' => null,
                        'message' => 'No transcription job currently available',
                    ],
                ]], 404);
            }

            $enrichmentLock = $lockFactory->createLock(sprintf('transcibing-enrichment-%s', $enrichment->getId()));
            if ($enrichmentLock->acquire()) {
                $mediaFilePath = sprintf('%s/%s', $enrichment->getMedia()->getFileDirectory(), $enrichment->getMedia()->getFileName());
                $mediaTemporaryUrl = $fileUploadService->generatePublicLink($mediaFilePath);

                if ($enrichment->getTransribingStartedAt() instanceof DateTimeInterface) {
                    $enrichment->setRetries($enrichment->getRetries() + 1);
                }

                $enrichment
                    ->setStatus(Enrichment::STATUS_TRANSCRIBING_MEDIA)
                    ->setTransribingStartedAt(new DateTime())
                    ->setTranscribedBy($clientEntity)
                    ->setTranscriptionTaskId(Uuid::fromString($taskId))
                ;
                $clientEntity->setJobLastTakendAt(new DateTime());
                $entityManager->flush();
                $enrichmentLock->release();

                $transcriptionJobResponse = (new TranscriptionJobResponse())
                    ->setEnrichmentId($enrichment->getId())
                    ->setMediaTemporaryUrl($mediaTemporaryUrl)
                    ->setLanguage($enrichment->getLanguage())
                ;

                return $this->json($transcriptionJobResponse);
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

    private function validateEnrichmentAccess(?Enrichment $enrichment, string $id, string $taskId): ?JsonResponse
    {
        if (!$enrichment instanceof Enrichment) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("No enrichment with ID '%s' has been found", $id),
                ],
            ]], 404);
        }

        if ($enrichment->isDeleted()) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => 'id',
                    'message' => sprintf("The enrichment that you want to get '%s' has been deleted", $id),
                ],
            ]], 404);
        }

        if (
            $enrichment->getTranscribedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')
            || (string) $enrichment->getTranscriptionTaskId() !== $taskId
        ) {
            return $this->json(['status' => 'KO', 'errors' => [
                [
                    'path' => null,
                    'message' => 'You are not allowed to access this enrichment',
                ],
            ]], 403);
        }

        return null;
    }
}
