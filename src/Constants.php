<?php

namespace App;

use League\Bundle\OAuth2ServerBundle\OAuth2Grants;

final class Constants
{
    public const string ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const string ROLE_USER = 'ROLE_USER';
    public const string SCOPE_DEFAULT = 'scope_default';
    public const string SCOPE_CLIENT = 'scope_client';
    public const string SCOPE_PROCESSING_WORKER = 'scope_processing_worker';
    public const string SCOPE_TRANSCRIPTION_WORKER = 'scope_transcription_worker';
    public const string SCOPE_EVALUATION_WORKER = 'scope_evaluation_worker';
    public const string SCOPE_TRANSLATION_WORKER = 'scope_translation_worker';
    public const array SORT_ORDER_OPTIONS = ['asc', 'desc'];
    public const string TEMPORARY_STORAGE_FOR_WORKER_PATH = './public/tmp/medias';
    public const string MEDIAS_PREFIX = 'medias';
    public const string EVALUATOR_CHAT_GPT = 'ChatGPT';
    public const string PARAMETER_MAX_TRANSCRIPTION_RETRIES = 'MAX_TRANSCRIPTION_RETRIES';
    public const string PARAMETER_MAX_ENRICHMENT_RETRIES = 'MAX_ENRICHMENT_RETRIES';
    public const string PARAMETER_MAX_TRANSLATION_RETRIES = 'MAX_TRANSLATION_RETRIES';
    public const string PARAMETER_MAX_EVALUATION_RETRIES = 'MAX_EVALUATION_RETRIES';
    public const string PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS = 'MAX_MEDIA_DURATION_IN_SECONDS';
    public const string PARAMETER_MAX_TEXT_LENGTH = 'MAX_TEXT_LENGTH';
    public const string PARAMETER_TRANSCRIPTION_WORKER_TIMEOUT_IN_MINUTES = 'TRANSCRIPTION_WORKER_TIMEOUT_IN_MINUTES';
    public const string PARAMETER_AI_ENRICHMENT_WORKER_TIMEOUT_IN_MINUTES = 'AI_ENRICHMENT_WORKER_TIMEOUT_IN_MINUTES';
    public const string PARAMETER_TRANSLATION_WORKER_TIMEOUT_IN_MINUTES = 'TRANSLATION_WORKER_TIMEOUT_IN_MINUTES';
    public const string PARAMETER_AI_EVALUATION_WORKER_TIMEOUT_IN_MINUTES = 'AI_EVALUATION_WORKER_TIMEOUT_IN_MINUTES';
    public const string NOTIFICATION_LEVEL_DEFAULT = 'default';
    public const string NOTIFICATION_LEVEL_TASK = 'task';

    public static function getEvaluators(): array
    {
        return [
            self::EVALUATOR_CHAT_GPT => self::EVALUATOR_CHAT_GPT,
        ];
    }

    public static function getMandatoryParameters(): array
    {
        return [
            self::PARAMETER_MAX_TRANSCRIPTION_RETRIES => self::PARAMETER_MAX_TRANSCRIPTION_RETRIES,
            self::PARAMETER_MAX_ENRICHMENT_RETRIES => self::PARAMETER_MAX_ENRICHMENT_RETRIES,
            self::PARAMETER_MAX_TRANSLATION_RETRIES => self::PARAMETER_MAX_TRANSLATION_RETRIES,
            self::PARAMETER_MAX_EVALUATION_RETRIES => self::PARAMETER_MAX_EVALUATION_RETRIES,
            self::PARAMETER_TRANSCRIPTION_WORKER_TIMEOUT_IN_MINUTES => self::PARAMETER_TRANSCRIPTION_WORKER_TIMEOUT_IN_MINUTES,
            self::PARAMETER_AI_ENRICHMENT_WORKER_TIMEOUT_IN_MINUTES => self::PARAMETER_AI_ENRICHMENT_WORKER_TIMEOUT_IN_MINUTES,
            self::PARAMETER_TRANSLATION_WORKER_TIMEOUT_IN_MINUTES => self::PARAMETER_TRANSLATION_WORKER_TIMEOUT_IN_MINUTES,
            self::PARAMETER_AI_EVALUATION_WORKER_TIMEOUT_IN_MINUTES => self::PARAMETER_AI_EVALUATION_WORKER_TIMEOUT_IN_MINUTES,
            self::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS => self::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS,
            self::PARAMETER_MAX_TEXT_LENGTH => self::PARAMETER_MAX_TEXT_LENGTH,
        ];
    }

    public static function getNotificationLevels(): array
    {
        return [
            self::NOTIFICATION_LEVEL_DEFAULT => self::NOTIFICATION_LEVEL_DEFAULT,
            self::NOTIFICATION_LEVEL_TASK => self::NOTIFICATION_LEVEL_TASK,
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
            'Translation Worker' => Constants::SCOPE_TRANSLATION_WORKER,
        ];
    }

    public static function getAvailableGrants(): array
    {
        return [
            'client_crendentials' => OAuth2Grants::CLIENT_CREDENTIALS,
        ];
    }

    public const string ROLE_OAUTH2_PREFIX = 'ROLE_OAUTH2_';
}
