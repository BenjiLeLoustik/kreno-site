<?php

namespace App\Controller\Front;

use App\User\Enum\PasswordSpace;
use App\User\Mapper\PasswordRequestMapper;
use App\User\Provider\PasswordPageProvider;
use App\User\Service\PasswordService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class PasswordController extends AbstractFrontController
{
    public function __construct(
        private PasswordService $passwords,
        private PasswordPageProvider $pages,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET'])]
    public function forgotForm(Request $request): Response
    {
        $page = $this->pages->forgot($request, PasswordSpace::CLIENT, $this->optionalUser());

        return $this->render('front/security/forgot_password.html.twig', $page);
    }

    #[Route('/pro/forgot-password', name: 'pro_forgot_password', methods: ['GET'])]
    public function proForgotForm(Request $request): Response
    {
        $page = $this->pages->forgot($request, PasswordSpace::PRO, $this->optionalUser());

        return $this->render('front/security/forgot_password.html.twig', $page);
    }

    #[Csrf('account')]
    #[Route('/forgot-password', name: 'app_forgot_password_submit', methods: ['POST'])]
    public function forgot(Request $request): RedirectResponse
    {
        $input = PasswordRequestMapper::forgot($request);

        if ($this->invalid($input)) {
            return $this->redirectToRoute(PasswordSpace::CLIENT->forgotRoute(), ['email' => $input->email]);
        }

        $this->passwords->requestReset($input->email, PasswordSpace::CLIENT->value);

        return $this->redirectToRoute(PasswordSpace::CLIENT->forgotRoute(), ['sent' => 1, 'email' => $input->email]);
    }

    #[Csrf('account')]
    #[Route('/pro/forgot-password', name: 'pro_forgot_password_submit', methods: ['POST'])]
    public function proForgot(Request $request): RedirectResponse
    {
        $input = PasswordRequestMapper::forgot($request);

        if ($this->invalid($input)) {
            return $this->redirectToRoute(PasswordSpace::PRO->forgotRoute(), ['email' => $input->email]);
        }

        $this->passwords->requestReset($input->email, PasswordSpace::PRO->value);

        return $this->redirectToRoute(PasswordSpace::PRO->forgotRoute(), ['sent' => 1, 'email' => $input->email]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET'])]
    public function check(string $token): Response
    {
        $page = $this->pages->reset($token, PasswordSpace::CLIENT);

        return $this->render('front/security/reset_password.html.twig', $page);
    }

    #[Route('/pro/reset-password/{token}', name: 'pro_reset_password', methods: ['GET'])]
    public function proCheck(string $token): Response
    {
        $page = $this->pages->reset($token, PasswordSpace::PRO);

        return $this->render('front/security/reset_password.html.twig', $page);
    }

    #[Csrf('account')]
    #[Route('/reset-password/{token}', name: 'app_reset_password_submit', methods: ['POST'])]
    public function reset(Request $request, string $token): RedirectResponse
    {
        $input = PasswordRequestMapper::reset($request);
        $space = PasswordSpace::CLIENT;

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->passwords->reset($token, $input),
                $space->resetFlash(),
            )) {
            return $this->redirectToRoute($space->loginRoute());
        }

        return $this->redirectToRoute($space->resetRoute(), ['token' => $token]);
    }

    #[Csrf('account')]
    #[Route('/pro/reset-password/{token}', name: 'pro_reset_password_submit', methods: ['POST'])]
    public function proReset(Request $request, string $token): RedirectResponse
    {
        $input = PasswordRequestMapper::reset($request);
        $space = PasswordSpace::PRO;

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->passwords->reset($token, $input),
                $space->resetFlash(),
            )) {
            return $this->redirectToRoute($space->loginRoute());
        }

        return $this->redirectToRoute($space->resetRoute(), ['token' => $token]);
    }
}