<?php

namespace App\Utils;

class MimeTypeUtils
{
    public function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    public function isAudio(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'audio/');
    }

    public function isPlainText(string $mimeType): bool
    {
        return 'text/plain' === $mimeType;
    }
}
