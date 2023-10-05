<?php

namespace App\Service;

use App\Entity\Administrator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserManager
{
    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function updatePassword(UserInterface $user): void
    {
        if (!$user instanceof Administrator || !$user->getPlainPassword()) {
            return;
        }
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }
}
