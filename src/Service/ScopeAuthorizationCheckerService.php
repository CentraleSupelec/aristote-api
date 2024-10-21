<?php

namespace App\Service;

use App\Constants;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ScopeAuthorizationCheckerService
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return $this->authorizationChecker->isGranted(Constants::ROLE_OAUTH2_PREFIX.strtoupper($scope));
    }
}
