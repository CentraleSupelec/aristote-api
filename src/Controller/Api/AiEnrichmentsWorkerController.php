<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Form\AiEnrichmentRequestPayloadType;
use App\Model\AiEnrichmentRequestPayload;
use App\Model\ErrorsResponse;
use App\Repository\EnrichmentRepository;
use App\Repository\EnrichmentVersionRepository;
use App\Service\ApiClientManager;
use App\Service\ScopeAuthorizationCheckerService;
use App\Service\VideoUploadService;
use App\Utils\PaginationUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1')]
class AiEnrichmentsWorkerController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils
    ) {
    }

    #[OA\Tag(name: 'AI Enrichment - Worker')]
    #[OA\Post(
        description: 'Completes an initial version of an enrichment with AI',
        summary: 'Completes an initial version of an enrichment with AI'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: AiEnrichmentRequestPayload::class),
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
    #[Route('/versions/{versionId}/ai_enrichment', name: 'complete_initial_enrichment_version', methods: ['POST'])]
    public function completeInitialEnrichmentVersionsByEnrichmentVersionID(
        string $versionId,
        Request $request,
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_PROCESSING_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $uuidValidationErrorResponse = $this->validateUuid($versionId);
        if ($uuidValidationErrorResponse instanceof JsonResponse) {
            return $uuidValidationErrorResponse;
        }

        $enrichmentVersion = $enrichmentVersionRepository->findOneBy(['id' => $versionId]);

        $enrichmentVersionAccessErrorResponse = $this->validateEnrichmentVersionAccess($enrichmentVersion, $versionId);
        if ($enrichmentVersionAccessErrorResponse instanceof JsonResponse) {
            return $enrichmentVersionAccessErrorResponse;
        }

        if (!$enrichmentVersion->isInitialVersion()) {
            return $this->json([
                'status' => 'KO',
                'errors' => ['This enrichment version is not an initial version waiting for AI Enrichment'],
            ], 403);
        }

        $aiEnrichmentRequestPayload = new AiEnrichmentRequestPayload();
        $form = $this->createForm(AiEnrichmentRequestPayloadType::class, $aiEnrichmentRequestPayload);
        $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $form->submit($requestBody);

        $errors = $this->validator->validate($aiEnrichmentRequestPayload);
        if (0 === $errors->count() && $form->isValid()) {
            $enrichmentVersion->setEnrichmentVersionMetadata($aiEnrichmentRequestPayload->getEnrichmentVersionMetadata());

            $multipleChoiceQuestions = $aiEnrichmentRequestPayload->getMultipleChoiceQuestions();
            foreach ($multipleChoiceQuestions as $multipleChoiceQuestion) {
                $enrichmentVersion->addMultipleChoiceQuestion($multipleChoiceQuestion);
            }

            $enrichmentVersion->getEnrichment()->setStatus(Enrichment::STATUS_SUCCESS)->setAiEnrichmentEndedAt(new DateTime());

            $errors = $this->validator->validate($enrichmentVersion);
            if (count($errors) > 0) {
                $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

                return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
            }

            $entityManager->flush();

            return $this->json(['status' => 'OK']);
        } else {
            return $this->json(['errors' => $errors]);
        }
    }

    #[OA\Tag(name: 'AI Enrichment - Worker')]
    #[OA\Get(
        description: 'Get an enrichment job',
        summary: 'Get an enrichment job'
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns an enrichment',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'id',
                    description: 'Enrichment ID',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'mediaTemporaryUrl',
                    description: 'Media Temporary Url',
                    type: 'string'
                ),
            ],
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
    #[Route('/enrichments/job/ai_enrichment/oldest', name: 'get_enrichment_job', methods: ['GET'])]
    public function getEnrichmentJob(
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        VideoUploadService $videoUploadService
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_PROCESSING_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $retryTimes = 2;
        for ($i = 0; $i < $retryTimes; ++$i) {
            $enrichment = $enrichmentRepository->findOldestEnrichmentInWaitingAiEnrichmentStatusOrAiEnrichmentStatusForMoreThanXMinutes(1);

            if (!$enrichment instanceof Enrichment) {
                return $this->json(['status' => 'KO', 'errors' => ['No job currently available']], 404);
            }

            if (1 !== $enrichment->getVersions()->count()) {
                return $this->json(['status' => 'KO', 'errors' => ['No or more than one versions have been found for the eligible enrichment, please report this issue']], 404);
            }

            $enrichmentLock = $lockFactory->createLock(sprintf('enrichment-%s', $enrichment->getId()));
            if ($enrichmentLock->acquire()) {
                $enrichmentVersion = $enrichment->getVersions()->get(0);
                $enrichment
                    ->setStatus(Enrichment::STATUS_AI_ENRICHING)
                    ->setAiEnrichmentStartedAt(new DateTime())
                    ->setAiProcessedBy($clientEntity)
                ;
                $entityManager->flush();
                $enrichmentLock->release();

                $options = [
                    AbstractNormalizer::GROUPS => ['enrichment_versions'],
                ];

                return $this->json([
                    'enrichmentVersionId' => $enrichmentVersion->getId(),
                    'transcript' => $enrichmentVersion->getTranscript(),
                    'disciplines' => $enrichment->getDisciplines(),
                    'mediaTypes' => $enrichment->getMediaTypes(),
                ], context: $options);
            }
        }

        return $this->json(['status' => 'KO', 'errors' => ['No job currently available']], 404);
    }

    private function validateUuid(string $id): ?JsonResponse
    {
        $constraintViolationList = $this->validator->validate($id, new Uuid());

        if ($constraintViolationList->count() > 0) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("'%s' is not a valid UUID", $id)]], 400);
        }

        return null;
    }

    private function validateEnrichmentVersionAccess(EnrichmentVersion|null $enrichmentVersion, string $id): ?JsonResponse
    {
        if (!$enrichmentVersion instanceof EnrichmentVersion) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No enrichment version with ID '%s' has been found", $id)]], 404);
        }

        if ($enrichmentVersion->getEnrichment()->getAiProcessedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')) {
            return $this->json(['status' => 'KO', 'errors' => ['You are not allowed to access this enrichment version']], 403);
        }

        return null;
    }
}
