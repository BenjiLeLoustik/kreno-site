<?php

namespace App\Controller\Front;

use App\Entity\User\User;
use App\User\Dto\RegistrationInput;
use App\User\Service\RegistrationService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class RegistrationController extends AbstractFrontController
{
    public const OLD_INPUT = 'app.signup.old';

    public function __construct(
        private RegistrationService $registration,
        private SessionManagerInterface $session,
    ) {
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET'])]
    public function form(): Response
    {
        if ($this->optionalUser() !== null) {
            return $this->redirectToRoute('home_index');
        }

        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);

        return $this->render('front/security/signup.html.twig', [
            'form' => (is_array($old) ? $old : []) + ['firstName' => null, 'lastName' => null, 'email' => null, 'phone' => null],
        ]);
    }

    #[Csrf('account')]
    #[Route('/signup', name: 'app_signup_submit', methods: ['POST'])]
    public function register(Request $request): RedirectResponse
    {
        $input = new RegistrationInput();
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName')) ?: null;
        $input->email = trim($request->request->getString('email'));
        $input->phone = trim($request->request->getString('phone')) ?: null;
        $input->password = $request->request->getString('password');
        $input->acceptTerms = $request->request->getBoolean('acceptTerms');
        $user = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, &$user): void {
                $user = $this->registration->register($input);
            });
        }

        if (!$user instanceof User) {
            $this->session->set(self::OLD_INPUT, ['firstName' => $input->firstName, 'lastName' => $input->lastName, 'email' => $input->email, 'phone' => $input->phone]);

            return $this->redirectToRoute('app_signup');
        }

        $this->loginUser($user);
        $this->addFlash('success', sprintf('Bienvenue %s ! Un e-mail de confirmation vient d\'être envoyé à %s.', $user->getFirstName(), $user->getEmail()));

        return $this->redirectToRoute('home_index');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verify(string $token): RedirectResponse
    {
        $this->attempt(fn () => $this->registration->verifyEmail($token), 'Ton adresse e-mail est confirmée. Merci !');

        return $this->redirectToRoute($this->optionalUser() !== null ? 'home_index' : 'app_login');
    }

    #[Csrf('account')]
    #[Route('/account/verify-email/resend', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resend(): RedirectResponse
    {
        $user = $this->currentUser();
        $this->attempt(fn () => $this->registration->resendVerification($user), sprintf('Un nouveau lien de confirmation a été envoyé à %s.', $user->getEmail()));

        return $this->redirectToRoute('home_index');
    }
}