<?php

namespace App\Controller\Front;

use App\User\Dto\ForgotPasswordInput;
use App\User\Dto\ResetPasswordInput;
use App\User\Service\PasswordService;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class PasswordController extends AbstractFrontController
{
    public function __construct(private PasswordService $passwords)
    {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET'])]
    public function forgotForm(Request $request): Response
    {
        return $this->forgotPage($request, false);
    }

    #[Route('/pro/forgot-password', name: 'pro_forgot_password', methods: ['GET'])]
    public function proForgotForm(Request $request): Response
    {
        return $this->forgotPage($request, true);
    }

    #[Csrf('account')]
    #[Route('/forgot-password', name: 'app_forgot_password_submit', methods: ['POST'])]
    public function forgot(#[MapRequestPayload(validate: false)] ForgotPasswordInput $input): RedirectResponse
    {
        return $this->requestReset($input, false);
    }

    #[Csrf('account')]
    #[Route('/pro/forgot-password', name: 'pro_forgot_password_submit', methods: ['POST'])]
    public function proForgot(#[MapRequestPayload(validate: false)] ForgotPasswordInput $input): RedirectResponse
    {
        return $this->requestReset($input, true);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET'])]
    public function check(string $token): Response
    {
        return $this->resetPage($token, false);
    }

    #[Route('/pro/reset-password/{token}', name: 'pro_reset_password', methods: ['GET'])]
    public function proCheck(string $token): Response
    {
        return $this->resetPage($token, true);
    }

    #[Csrf('account')]
    #[Route('/reset-password/{token}', name: 'app_reset_password_submit', methods: ['POST'])]
    public function reset(string $token, #[MapRequestPayload(validate: false)] ResetPasswordInput $input): RedirectResponse
    {
        return $this->resetPassword($token, $input, false);
    }

    #[Csrf('account')]
    #[Route('/pro/reset-password/{token}', name: 'pro_reset_password_submit', methods: ['POST'])]
    public function proReset(string $token, #[MapRequestPayload(validate: false)] ResetPasswordInput $input): RedirectResponse
    {
        return $this->resetPassword($token, $input, true);
    }

    private function forgotPage(Request $request, bool $pro): Response
    {
        return $this->render('front/security/forgot_password.html.twig', [
            'pro' => $pro,
            'sent' => $request->query->getBoolean('sent'),
            'email' => $request->query->getString('email') ?: $this->optionalUser()?->getEmail(),
        ]);
    }

    private function requestReset(ForgotPasswordInput $input, bool $pro): RedirectResponse
    {
        $input->email = mb_strtolower(trim($input->email));
        $route = $pro ? 'pro_forgot_password' : 'app_forgot_password';

        if ($this->invalid($input)) {
            return $this->redirectToRoute($route, ['email' => $input->email]);
        }

        $this->passwords->requestReset($input->email, $pro ? 'pro' : 'client');

        return $this->redirectToRoute($route, ['sent' => 1, 'email' => $input->email]);
    }

    private function resetPage(string $token, bool $pro): Response
    {
        try {
            $email = $this->passwords->checkResetToken($token)->getEmail();
        } catch (BusinessRuleException) {
            $email = null;
        }

        return $this->render('front/security/reset_password.html.twig', [
            'pro' => $pro,
            'token' => $token,
            'valid' => $email !== null,
            'email' => $email,
        ]);
    }

    private function resetPassword(string $token, ResetPasswordInput $input, bool $pro): RedirectResponse
    {
        if (!$this->invalid($input) && $this->attempt(fn () => $this->passwords->reset($token, $input), $pro ? 'Votre mot de passe a été modifié. Vous pouvez vous connecter.' : 'Ton mot de passe a été modifié. Tu peux te connecter.')) {
            return $this->redirectToRoute($pro ? 'pro_login' : 'app_login');
        }

        return $this->redirectToRoute($pro ? 'pro_reset_password' : 'app_reset_password', ['token' => $token]);
    }
}