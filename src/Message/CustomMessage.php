<?php

namespace App\Message;

class CustomMessage
{
    public function __construct(
        private readonly string $content,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
