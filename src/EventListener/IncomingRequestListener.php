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
    }
}
