<?php

namespace App\Controller\Sonata;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $authenticationException = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $value = $csrfTokenManager->getToken('authenticate')->getValue();

        return $this->render('sonata/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $authenticationException,
            'csrf_token' => $value,
        ]);
    }
}
