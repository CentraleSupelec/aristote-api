<?php

namespace App\Utils;

class MimeTypeUtils
{
    public function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    public function isPlainText(string $mimeType): bool
    {
        return 'text/plain' === $mimeType;
    }
}
