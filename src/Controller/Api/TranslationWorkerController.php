<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Choice;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\MultipleChoiceQuestion;
use App\Entity\Transcript;
use App\Form\TranslationRequestPayloadType;
use App\Model\EnrichmentWebhookPayload;
use App\Model\ErrorsResponse;
use App\Model\TranslationJobResponse;
use App\Model\TranslationRequestPayload;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Service\ApiClientManager;
use App\Service\ScopeAuthorizationCheckerService;
use App\Utils\PaginationUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Flysystem\FilesystemOperator;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/v1')]
class TranslationWorkerController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils
    ) {
    }

    #[OA\Tag(name: 'Translation - Worker')]
    #[OA\Post(
        description: 'Translate enrichment version',
        summary: 'Translate enrichment version'
    )]
    #[OA\RequestBody(
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: TranslationRequestPayload::class, groups: ['Default', 'multipart']))
            ),
            new OA\JsonContent(
                type: 'object',
                ref: new Model(type: TranslationRequestPayload::class, groups: ['Default', 'json']),
            ),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Translated the enrichment version successfully',
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
    #[Route('/enrichments/{enrichmentId}/versions/{versionId}/translation', name: 'translate_enrichment_version', methods: ['POST'])]
    public function translateEnrichmentVersion(
        string $enrichmentId,
        string $versionId,
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        HttpClientInterface $httpClient,
        FilesystemOperator $mediaStorage
    ): Response {
        $requestBody = [];
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSLATION_WORKER)) {
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
        $translationRequestPayload = new TranslationRequestPayload();
        $form = $this->createForm(TranslationRequestPayloadType::class, $translationRequestPayload);

        if ('json' === $request->getContentTypeFormat()) {
            $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (array_key_exists('transcript', $requestBody) && array_key_exists('sentences', $requestBody['transcript'])) {
                $requestBody['transcript']['sentences'] = json_encode($requestBody['transcript']['sentences'], JSON_THROW_ON_ERROR);
            }
        } else {
            $requestBody['transcriptFile'] = $request->files->get('transcriptFile');
            $enrichmentVersionMetadata = $request->request->get('enrichmentVersionMetadata');
            if ($enrichmentVersionMetadata) {
                $requestBody['enrichmentVersionMetadata'] = json_decode($enrichmentVersionMetadata, true, 512, JSON_THROW_ON_ERROR);
            }

            $mcqs = $request->request->get('multipleChoiceQuestions');
            if ($mcqs) {
                $requestBody['multipleChoiceQuestions'] = json_decode($mcqs, true, 512, JSON_THROW_ON_ERROR);
            }

            $requestBody['taskId'] = $request->request->get('taskId');
            $requestBody['status'] = $request->request->get('status');
            $requestBody['failureCause'] = $request->request->get('failureCause');
        }

        $form->submit($requestBody);

        if ($form->isValid()) {
            $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

            $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess(
                $enrichmentVersion,
                $versionId,
                $enrichmentId,
                $translationRequestPayload->getTaskId(),
            );
            if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
                return $enrichmentVersionAccessErrorResponse;
            }

            $latestEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichmentId);
            if (!$enrichmentVersion->getId()->equals($latestEnrichmentVersion->getId()) || !$enrichmentVersion->isAiGenerated()) {
                return $this->json([
                    'status' => 'KO',
                    'errors' => ['This enrichment version is not a placeholder AI generated version waiting for translation'],
                ], 403);
            }

            $enrichment = $enrichmentVersion->getEnrichment();

            if ('KO' === $translationRequestPayload->getStatus()) {
                $enrichment->setFailureCause($translationRequestPayload->getFailureCause());
                $enrichment->setStatus(Enrichment::STATUS_FAILURE);
                $enrichment->getTranslatedBy()->setJobLastFailuredAt(new DateTime());
                $entityManager->flush();

                return $this->json(['status' => 'OK']);
            }

            if ($translationRequestPayload->getTranscriptFile() instanceof UploadedFile) {
                $translatedTranscriptContent = json_decode($translationRequestPayload->getTranscriptFile()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                $enrichmentVersion->getTranscript()
                    ->setTranslatedText($translatedTranscriptContent['text'])
                    ->setTranslatedSentences(json_encode($translatedTranscriptContent['sentences'], JSON_THROW_ON_ERROR))
                ;
            } elseif ($translationRequestPayload->getTranscript() instanceof Transcript) {
                $enrichmentVersion->getTranscript()
                    ->setTranslatedText($translationRequestPayload->getTranscript()->getText())
                    ->setTranslatedSentences($translationRequestPayload->getTranscript()->getSentences())
                ;
            }

            $enrichmentVersion->getEnrichmentVersionMetadata()
                ->setTranslatedTitle($translationRequestPayload->getEnrichmentVersionMetadata()->getTitle())
                ->setTranslatedDescription($translationRequestPayload->getEnrichmentVersionMetadata()->getDescription())
                ->setTranslatedTopics($translationRequestPayload->getEnrichmentVersionMetadata()->getTopics())
            ;

            $translatedMultipleChoiceQuestions = $translationRequestPayload->getMultipleChoiceQuestions();

            foreach ($translatedMultipleChoiceQuestions as $translatedMultipleChoiceQuestion) {
                $mcq = $enrichmentVersion->getMultipleChoiceQuestions()->findFirst(
                    fn (int $index, MultipleChoiceQuestion $multipleChoiceQuestion) => $multipleChoiceQuestion->getId()->equals($translatedMultipleChoiceQuestion->getId())
                );
                if ($mcq instanceof MultipleChoiceQuestion) {
                    $mcq
                        ->setTranslatedQuestion($translatedMultipleChoiceQuestion->getQuestion())
                        ->setTranslatedExplanation($translatedMultipleChoiceQuestion->getExplanation())
                    ;

                    $translatedChoices = $translatedMultipleChoiceQuestion->getChoices();

                    foreach ($translatedChoices as $translatedChoice) {
                        $choice = $mcq->getChoices()->findFirst(
                            fn (int $index, Choice $choice) => $choice->getId()->equals($translatedChoice->getId())
                        );
                        if ($choice instanceof Choice) {
                            $choice->setTranslatedOptionText($translatedChoice->getOptionText());
                        }
                    }
                }
            }

            $targetStatus = Enrichment::STATUS_SUCCESS;
            if ($enrichment->getAiEvaluation()) {
                $targetStatus = Enrichment::STATUS_WAITING_AI_EVALUATION;
            }

            $enrichment->setStatus($targetStatus)->setTranslationEndedAt(new DateTime());

            $errors = $this->validator->validate($enrichmentVersion);
            if (count($errors) > 0) {
                $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

                return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
            }

            if (Enrichment::STATUS_SUCCESS === $targetStatus) {
                $enrichmentWebhookPayload = (new EnrichmentWebhookPayload())
                    ->setId($enrichment->getId())
                    ->setStatus($enrichment->getStatus())
                    ->setFailureCause($enrichment->getFailureCause())
                    ->setInitialVersionId($enrichmentVersion->getId())
                ;

                try {
                    $serialized = $this->serializer->serialize($enrichmentWebhookPayload, 'json');

                    $response = $httpClient->request('POST', $enrichment->getNotificationWebhookUrl(), [
                        'body' => $serialized,
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ]);

                    $enrichment->setNotificationStatus($response->getStatusCode());
                    if (200 === $response->getStatusCode()) {
                        $enrichment->setNotifiedAt(new DateTime());
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    $enrichment->setNotificationStatus($e->getCode());
                }
            }
            $enrichment->getTranslatedBy()->setJobLastSuccessAt(new DateTime());
            $entityManager->flush();

            return $this->json(['status' => 'OK']);
        } else {
            $errors = $form->getErrors(deep: true);
            $errorMessages = [];
            foreach ($errors as $error) {
                /* @var FormError $error */
                $errorMessages[] = sprintf("Error on field '%s' : %s", $error->getOrigin()->getName(), $error->getMessage());
            }

            return $this->json(['status' => 'KO', 'errors' => $errorMessages], 400);
        }
    }

    #[OA\Tag(name: 'Translation - Worker')]
    #[OA\Get(
        description: 'Get a translation job',
        summary: 'Get a translation job'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a job',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: TranslationJobResponse::class),
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
    #[Route('/enrichments/job/translation/oldest', name: 'get_translation_job', methods: ['GET'])]
    public function getTranslationJob(
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        EnrichmentVersionRepository $enrichmentVersionRepository
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSLATION_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
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
            $enrichment = $enrichmentRepository->findOldestEnrichmentInWaitingTranslationStatusOrTranslatingStatusForMoreThanXMinutes();

            if (!$enrichment instanceof Enrichment) {
                return $this->json(['status' => 'KO', 'errors' => ['No job currently available']], 404);
            }

            $latestEnrichmentVersion = $enrichmentVersionRepository->findLatestVersionByEnrichmentId($enrichment->getId());

            if (!$latestEnrichmentVersion->isAiGenerated()) {
                return $this->json(['status' => 'KO', 'errors' => [sprintf('No enrichment version prepared for the eligible enrichment (%s), please report this issue', $enrichment->getId())]], 404);
            }

            $enrichmentLock = $lockFactory->createLock(sprintf('translating-enrichment-%s', $enrichment->getId()));
            if ($enrichmentLock->acquire()) {
                if (Enrichment::STATUS_WAITING_TRANSLATION !== $enrichment->getStatus()) {
                    $enrichment->setRetries($enrichment->getRetries() + 1);
                }

                $enrichment
                    ->setStatus(Enrichment::STATUS_TRANSLATING)
                    ->setTranslationStartedAt(new DateTime())
                    ->setTranslatedBy($clientEntity)
                    ->setTranslationTaskId(Uuid::fromString($taskId))
                ;
                $clientEntity->setJobLastTakendAt(new DateTime());
                $entityManager->flush();
                $enrichmentLock->release();

                $options = [
                    AbstractNormalizer::GROUPS => ['translation_job'],
                ];

                $translationJobResponse = (new TranslationJobResponse())
                    ->setEnrichmentId($enrichment->getId())
                    ->setEnrichmentVersionId($latestEnrichmentVersion->getId())
                    ->setTranscript($latestEnrichmentVersion->getTranscript())
                    ->setMultipleChoiceQuestions($latestEnrichmentVersion->getMultipleChoiceQuestions())
                    ->setEnrichmentVersionMetadata($latestEnrichmentVersion->getEnrichmentVersionMetadata())
                    ->setLanguage($enrichment->getLanguage())
                    ->setTranslateTo($enrichment->getTranslateTo())
                ;

                return $this->json($translationJobResponse, context: $options);
            }
        }

        return $this->json(['status' => 'KO', 'errors' => ['No job currently available']], 404);
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new UuidConstraint());

        if ($constraintViolationList->count() > 0) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("'%s' is not a valid UUID", $id)]], 400);
        }

        return null;
    }

    private function validateEnrichmentVersionAccess(?EnrichmentVersion $enrichmentVersion, string $enrichmentVersionId, string $enrichmentId, string $taskId): ?JsonResponse
    {
        if (!$enrichmentVersion instanceof EnrichmentVersion) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No enrichment version with ID '%s' has been found", $enrichmentVersionId)]], 404);
        }

        $enrichment = $enrichmentVersion->getEnrichment();

        if ($enrichment->getId()->toRfc4122() !== $enrichmentId) {
            return $this->json(['status' => 'KO', 'errors' => [
                sprintf('The enrichment id %s in the url is incorrect', $enrichmentVersionId, $enrichmentId),
            ]], 403);
        }

        if (
            $enrichment->getTranslatedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')
            || (string) $enrichment->getTranslationTaskId() !== $taskId
        ) {
            return $this->json(['status' => 'KO', 'errors' => ['You are not allowed to access this enrichment version']], 403);
        }

        return null;
    }
}
