<?php

namespace App\EventListener;

use App\Entity\Administrator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LastLoginListener
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $interactiveLoginEvent): void
    {
        $user = $interactiveLoginEvent->getAuthenticationToken()->getUser();

        if ($user instanceof Administrator) {
            $user->setLastLoginAt(new DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
