<?php

namespace App\Controller\Front;

use App\Company\Dto\AcceptInvitationInput;
use App\Company\Helper\RoleHelper;
use App\Company\Service\TeamService;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserRole;
use DateTimeImmutable;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;
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
    public function show(string $token): JsonResponse
    {
        $invitation = $this->team->findInvitation($token);
        $role = $invitation->getRole()?->value ?? $invitation->getEstablishmentRole()?->value ?? 'employee';
        $current = $this->optionalUser();

        return $this->json([
            'company' => $invitation->getCompany()?->getName(),
            'establishment' => $invitation->getEstablishment()?->getName(),
            'email' => $invitation->getEmail(),
            'role' => $role,
            'roleLabel' => RoleHelper::label($role),
            'hasAccount' => $this->users->emailExists((string) $invitation->getEmail()),
            'loggedInAs' => $current?->getEmail(),
        ]);
    }

    #[Route('/invitation/{token}', name: 'pro_invitation_accept', methods: ['POST'])]
    public function accept(
        string $token,
        #[MapRequestPayload]
        AcceptInvitationInput $input
    ): JsonResponse {
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
                ->setFirstName(trim($input->firstName))
                ->setLastName($input->lastName !== null ? (trim($input->lastName) ?: null) : null)
                ->setRoles([UserRole::USER->value])
                ->setEmailVerifiedAt(new DateTimeImmutable());

            $user->setPassword($this->hasher->hashPassword($user, $input->password));
            $this->orm->persist($user);
            $this->orm->flush();
            $this->loginUser($user);
        }

        $this->team->accept($token, $user);

        return $this->json(['joined' => true, 'redirect' => $this->generateUrl('pro_dashboard')]);
    }
}