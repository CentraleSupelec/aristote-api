<?php

namespace App\MessageHandler;

use App\Message\CustomMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CustomMessageHandler
{
    public function __invoke(CustomMessage $customMessage): void
    {
    }
}
