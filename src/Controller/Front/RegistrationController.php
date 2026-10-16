<?php

namespace App\Controller\Front;

use App\Entity\User\User;
use App\User\Enum\RegistrationFlash;
use App\User\Mapper\RegistrationRequestMapper;
use App\User\Provider\SignupPageProvider;
use App\User\Service\RegistrationService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractFrontController
{
    public function __construct(
        private RegistrationService $registration,
        private SignupPageProvider $pages,
    ) {
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET'])]
    public function form(): Response
    {
        if ($this->optionalUser() !== null) {
            return $this->redirectToRoute('home_index');
        }

        return $this->render('front/security/signup.html.twig', $this->pages->form());
    }

    #[Csrf('account')]
    #[Route('/signup', name: 'app_signup_submit', methods: ['POST'])]
    public function register(Request $request): RedirectResponse
    {
        $input = RegistrationRequestMapper::registration($request);
        $user = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, &$user): void {
                $user = $this->registration->register($input);
            });
        }

        if (!$user instanceof User) {
            $this->pages->remember(RegistrationRequestMapper::old($input));

            return $this->redirectToRoute('app_signup');
        }

        $this->loginUser($user);
        $this->addFlash('success', RegistrationFlash::WELCOME->text($user->getFirstName(), $user->getEmail()));

        return $this->redirectToRoute('home_index');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verify(string $token): RedirectResponse
    {
        $this->attempt(fn () => $this->registration->verifyEmail($token), RegistrationFlash::EMAIL_VERIFIED->text());

        if ($this->optionalUser() === null) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('home_index');
    }

    #[Csrf('account')]
    #[Route('/account/verify-email/resend', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resend(): RedirectResponse
    {
        $user = $this->currentUser();
        $this->attempt(
            fn () => $this->registration->resendVerification($user),
            RegistrationFlash::VERIFICATION_SENT->text($user->getEmail()),
        );

        return $this->redirectToRoute('home_index');
    }
}