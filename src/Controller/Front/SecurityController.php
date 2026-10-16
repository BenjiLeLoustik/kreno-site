<?php

namespace App\Controller\Front;

use App\User\Enum\LoginError;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class SecurityController extends AbstractFrontController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->optionalUser() !== null) {
            return $this->redirectToRoute('account_index');
        }

        return $this->render('front/security/login.html.twig', [
            'lastUsername' => $this->getLastUsername(),
            'error' => LoginError::message($this->getLastAuthenticationError()),
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
            'error' => LoginError::message($this->getLastAuthenticationError()),
            'targetPath' => $this->generateUrl('pro_dashboard'),
        ]);
    }
}