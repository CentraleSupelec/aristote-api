<?php

namespace App;

use League\Bundle\OAuth2ServerBundle\OAuth2Grants;

final class Constants
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const SCOPE_DEFAULT = 'scope_default';
    public const SORT_ORDER_OPTIONS = ['asc', 'desc'];
    public const TEMPORARY_STORAGE_PATH = './tmp/videos';

    public static function getAvailableScopes(): array
    {
        return [
            'Défaut' => Constants::SCOPE_DEFAULT,
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
