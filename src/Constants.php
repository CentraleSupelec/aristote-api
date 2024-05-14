<?php

namespace App;

use League\Bundle\OAuth2ServerBundle\OAuth2Grants;

final class Constants
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_USER = 'ROLE_USER';
    public const SCOPE_DEFAULT = 'scope_default';
    public const SCOPE_CLIENT = 'scope_client';
    public const SCOPE_PROCESSING_WORKER = 'scope_processing_worker';
    public const SCOPE_TRANSCRIPTION_WORKER = 'scope_transcription_worker';
    public const SCOPE_EVALUATION_WORKER = 'scope_evaluation_worker';
    public const SORT_ORDER_OPTIONS = ['asc', 'desc'];
    public const TEMPORARY_STORAGE_FOR_WORKER_PATH = './public/tmp/medias';
    public const MEDIAS_PREFIX = 'medias';
    public const EVALUATOR_CHAT_GPT = 'ChatGPT';

    public static function getEvaluators(): array
    {
        return [
            self::EVALUATOR_CHAT_GPT => self::EVALUATOR_CHAT_GPT,
        ];
    }

    public static function getAvailableScopes(): array
    {
        return [
            'Défaut' => Constants::SCOPE_DEFAULT,
            'Client' => Constants::SCOPE_CLIENT,
            'AI Enrichment Worker' => Constants::SCOPE_PROCESSING_WORKER,
            'Transcription Worker' => Constants::SCOPE_TRANSCRIPTION_WORKER,
            'AI Evaluation Worker' => Constants::SCOPE_EVALUATION_WORKER,
        ];
    }

    public static function getAvailableGrants(): array
    {
        return [
            'client_crendentials' => OAuth2Grants::CLIENT_CREDENTIALS,
        ];
    }

    public const ROLE_OAUTH2_PREFIX = 'ROLE_OAUTH2_';
}
