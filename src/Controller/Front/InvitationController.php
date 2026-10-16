<?php

namespace App\Controller\Front;

use App\Company\Dto\AcceptInvitationInput;
use App\Company\Helper\RoleHelper;
use App\Company\Service\TeamService;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserRole;
use App\User\Exception\InvalidTokenException;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class InvitationController extends AbstractFrontController
{
    public function __construct(
        private TeamService $team,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private OrmManagerInterface $orm,
    ) {
    }

    #[Route('/invitation/{token}', name: 'pro_invitation_show', methods: ['GET'])]
    public function show(string $token): Response
    {
        try {
            $invitation = $this->team->findInvitation($token);
        } catch (InvalidTokenException $exception) {
            return $this->render('front/invitation/show.html.twig', ['invitation' => null, 'error' => $exception->getMessage(), 'token' => $token, 'state' => null, 'loggedInAs' => null], 410);
        }

        $role = $invitation->getRole()?->value ?? $invitation->getEstablishmentRole()?->value ?? 'employee';
        $current = $this->optionalUser();
        $email = (string) $invitation->getEmail();

        return $this->render('front/invitation/show.html.twig', [
            'token' => $token,
            'error' => null,
            'invitation' => [
                'company' => $invitation->getCompany()?->getName(),
                'establishment' => $invitation->getEstablishment()?->getName(),
                'email' => $email,
                'roleLabel' => RoleHelper::label($role),
                'invitedBy' => $invitation->getInvitedBy()?->getFirstName(),
            ],
            'state' => match (true) {
                $current !== null && mb_strtolower((string) $current->getEmail()) === mb_strtolower($email) => 'ready',
                $current !== null => 'other',
                $this->users->emailExists($email) => 'login',
                default => 'signup',
            },
            'loggedInAs' => $current?->getEmail(),
        ]);
    }

    #[Csrf('invitation')]
    #[Route('/invitation/{token}', name: 'pro_invitation_accept', methods: ['POST'])]
    public function accept(Request $request, string $token): RedirectResponse
    {
        $input = new AcceptInvitationInput();
        $input->firstName = trim($request->request->getString('firstName')) ?: null;
        $input->lastName = trim($request->request->getString('lastName')) ?: null;
        $input->password = $request->request->getString('password') ?: null;

        if ($this->invalid($input)) {
            return $this->redirectToRoute('pro_invitation_show', ['token' => $token]);
        }

        $joined = $this->attempt(function () use ($token, $input): void {
            $invitation = $this->team->findInvitation($token);
            $user = $this->optionalUser();

            if ($user === null) {
                if ($this->users->emailExists((string) $invitation->getEmail())) {
                    throw new BusinessRuleException('Un compte existe déjà avec {email} : connectez-vous pour accepter l\'invitation.', ['email' => $invitation->getEmail()]);
                }

                if ($input->firstName === null || $input->password === null) {
                    throw new BusinessRuleException('Indiquez votre prénom et choisissez un mot de passe.');
                }

                $user = (new User())
                    ->setEmail((string) $invitation->getEmail())
                    ->setFirstName($input->firstName)
                    ->setLastName($input->lastName)
                    ->setRoles([UserRole::USER->value])
                    ->setEmailVerifiedAt(new DateTimeImmutable());

                $user->setPassword($this->hasher->hashPassword($user, $input->password));
                $this->orm->persist($user);
                $this->orm->flush();
                $this->loginUser($user);
            }

            $this->team->accept($token, $user);
        });

        if (!$joined) {
            return $this->redirectToRoute('pro_invitation_show', ['token' => $token]);
        }

        $this->addFlash('success', 'Bienvenue dans l\'équipe !');

        return $this->redirectToRoute('pro_dashboard');
    }
}