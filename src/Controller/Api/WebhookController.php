<?php

namespace App\Controller\Api;

use App\Form\EnrichmentWebhookPayloadType;
use App\Model\EnrichmentWebhookPayload;
use App\Model\ErrorsResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1')]
class WebhookController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[OA\Tag(name: 'Webhook')]
    #[OA\Post(
        description: 'Endpoint to receive notification when enrichment is treated',
        summary: 'Endpoint to receive notification when enrichment is treated'
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: EnrichmentWebhookPayload::class)
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Acknowledge notification reception',
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad parameters',
        content: new OA\JsonContent(
            ref: new Model(type: ErrorsResponse::class),
            type: 'object'
        )
    )]
    #[Route('/webhook', name: 'notify_webhook', methods: ['POST'])]
    public function receiveNotification(
        Request $request,
    ): Response {
        $enrichmentWebhookPayload = new EnrichmentWebhookPayload();
        $form = $this->createForm(EnrichmentWebhookPayloadType::class, $enrichmentWebhookPayload);
        $requestBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $form->submit($requestBody);

        if ($form->isValid()) {
            return $this->json(['status' => 'OK']);
        } else {
            $errorsArray = array_map(fn (FormError $error) => [
                'message' => $error->getMessage(),
                'path' => $error->getOrigin()->getName(),
            ], iterator_to_array($form->getErrors(deep: true)));

            return $this->json(['status' => 'KO', 'errors' => $errorsArray], Response::HTTP_BAD_REQUEST);
        }
    }
}
