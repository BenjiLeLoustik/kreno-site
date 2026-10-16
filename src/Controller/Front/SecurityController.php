<?php

namespace App\Controller\Front;

use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class SecurityController extends AbstractFrontController
{
    private const ERRORS = [
        'Invalid credentials.' => 'Adresse e-mail ou mot de passe incorrect.',
        'Invalid CSRF token.' => 'La page a expiré. Recharge-la et réessaie.',
    ];

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->optionalUser() !== null) {
            return $this->redirectToRoute('account_index');
        }

        return $this->render('front/security/login.html.twig', [
            'lastUsername' => $this->getLastUsername(),
            'error' => $this->errorMessage($this->getLastAuthenticationError()),
        ]);
    }

    #[Route('/pro/login', name: 'pro_login', methods: ['GET'])]
    public function proLogin(): Response
    {
        if ($this->optionalUser() !== null) {
            return $this->redirectToRoute('pro_dashboard');
        }

        return $this->render('pro/security/login.html.twig', [
            'lastUsername' => $this->getLastUsername(),
            'error' => $this->errorMessage($this->getLastAuthenticationError()),
            'targetPath' => $this->generateUrl('pro_dashboard'),
        ]);
    }

    private function errorMessage(?string $error): ?string
    {
        if ($error === null) {
            return null;
        }

        if (str_starts_with($error, 'Too many failed login attempts')) {
            return 'Trop de tentatives. Réessaie dans une minute.';
        }

        return self::ERRORS[$error] ?? $error;
    }
}