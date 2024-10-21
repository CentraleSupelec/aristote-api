<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class IncomingRequestListener
{
    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();

        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $requestEvent->getRequest()->setRequestFormat('json');
        }

        if ('create_enrichment_version' === $request->attributes->get('_route')) {
            $enrichmentVersionMetadata = $request->request->get('enrichmentVersionMetadata');
            $request->request->set('enrichmentVersionMetadata', json_decode($enrichmentVersionMetadata, true, 512, JSON_THROW_ON_ERROR));
            $multipleChoiceQuestions = $this->stringJsonObjectsToArray($request->request->get('multipleChoiceQuestions'));
            $request->request->set('multipleChoiceQuestions', $multipleChoiceQuestions);
        }
    }

    private function stringJsonObjectsToArray(?string $jsonString)
    {
        if (null === $jsonString) {
            return [];
        }

        if (str_starts_with($jsonString, '[')) {
            return json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } else {
            return json_decode(sprintf('[%s]', $jsonString), true, 512, JSON_THROW_ON_ERROR);
        }
    }
}
