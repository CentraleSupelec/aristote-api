<?php

namespace App\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandlerArgumentsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

final class RetryCountArgumentAdderMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $retryCount = $this->getRetryCountFromEnvelope($envelope);

        $envelope = $envelope->with(new HandlerArgumentsStamp([
            $retryCount,
        ]));

        return $stack->next()->handle($envelope, $stack);
    }

    private function getRetryCountFromEnvelope(Envelope $envelope): int
    {
        /** @var RedeliveryStamp|null $stamp */
        $stamp = $envelope->last(RedeliveryStamp::class);

        if (!$stamp instanceof RedeliveryStamp) {
            return 0;
        }

        return $stamp->getRetryCount();
    }
}
