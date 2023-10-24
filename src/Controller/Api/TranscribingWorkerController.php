<?php

namespace App\Controller\Api;

use App\Constants;
use App\Entity\Enrichment;
use App\Entity\EnrichmentVersion;
use App\Entity\Transcript;
use App\Model\EnrichmentTranscriptRequestPayload;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1')]
class TranscribingWorkerController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Security $security,
        private readonly PaginationUtils $paginationUtils
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
        ApiClientManager $apiClientManager,
        EnrichmentVersionRepository $enrichmentVersionRepository,
        EnrichmentRepository $enrichmentRepository,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSCRIPTION_WORKER)) {
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

        if (!$initialVersion) {
            return $this->json([
                'status' => 'KO',
                'errors' => ['There is already an initial version found for the enrichment'],
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
            return $this->json(['status' => 'KO', 'errors' => ['No transcript has been given']], 403);
        }

        $enrichmentVersion = (new EnrichmentVersion())
            ->setInitialVersion(true)
            ->setTranscript($newTranscript)
        ;

        $enrichment->addVersion($enrichmentVersion);
        $enrichment->setStatus(Enrichment::STATUS_WAITING_AI_ENRICHMENT)->setTransribingEndedAt(new DateTime());

        $errors = $this->validator->validate($enrichment);

        if (count($errors) > 0) {
            $errorsArray = array_map(fn ($error) => $error->getMessage(), iterator_to_array($errors));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], 400);
        }
        $entityManager->persist($enrichment);
        $entityManager->flush();

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
    #[Route('/enrichments/transcription/job/oldest', name: 'get_transcription_job', methods: ['GET'])]
    public function getEnrichmentJob(
        ApiClientManager $apiClientManager,
        EnrichmentRepository $enrichmentRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        ScopeAuthorizationCheckerService $scopeAuthorizationCheckerService,
        VideoUploadService $videoUploadService
    ): Response {
        if (!$scopeAuthorizationCheckerService->hasScope(Constants::SCOPE_TRANSCRIPTION_WORKER)) {
            return $this->json(['status' => 'KO', 'errors' => ['User not authorized to access this resource']], 403);
        }

        $clientId = $this->security->getToken()->getAttribute('oauth_client_id');
        $clientEntity = $apiClientManager->getClientEntity($clientId);

        $retryTimes = 2;
        for ($i = 0; $i < $retryTimes; ++$i) {
            $enrichment = $enrichmentRepository->findOldestEnrichmentInWaitingMediaTranscriptionStatusOrTranscribingMediaStatusForMoreThanXMinutes(1);

            if (!$enrichment instanceof Enrichment) {
                return $this->json(['status' => 'KO', 'errors' => ['No transcription job currently available']], 404);
            }

            $enrichmentLock = $lockFactory->createLock(sprintf('transcibing-enrichment-%s', $enrichment->getId()));
            if ($enrichmentLock->acquire()) {
                $mediaFilePath = sprintf('%s/%s', $enrichment->getMedia()->getFileDirectory(), $enrichment->getMedia()->getFileName());
                $mediaTemporaryUrl = $videoUploadService->generatePublicLink($mediaFilePath);
                $enrichment
                    ->setStatus(Enrichment::STATUS_TRANSCRBING_MEDIA)
                    ->setTransribingStartedAt(new DateTime())
                    ->setTranscribedBy($clientEntity)
                ;
                $entityManager->flush();
                $enrichmentLock->release();

                return $this->json([
                    'id' => $enrichment->getId(),
                    'mediaTemporaryUrl' => $mediaTemporaryUrl,
                ]);
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

    private function validateEnrichmentAccess(Enrichment|null $object, string $id): ?JsonResponse
    {
        if (!$object instanceof Enrichment) {
            return $this->json(['status' => 'KO', 'errors' => [sprintf("No enrichment with ID '%s' has been found", $id)]], 404);
        }

        $enrichment = $object instanceof EnrichmentVersion ? $object->getEnrichment() : $object;

        if ($enrichment->getTranscribedBy()->getIdentifier() !== $this->security->getToken()->getAttribute('oauth_client_id')) {
            return $this->json(['status' => 'KO', 'errors' => ['You are not allowed to access this enrichment']], 403);
        }

        return null;
    }
}