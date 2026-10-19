<?php

namespace App\Company\Provider;

use App\Company\Helper\RoleHelper;
use App\Company\Service\TeamService;
use App\Entity\Company\CompanyInvitation;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Repository\User\UserRepository;
use App\User\Exception\InvalidTokenException;

class InvitationPageProvider
{
    public const STATUS_GONE = 410;

    public const STATUS_OK = 200;

    public function __construct(
        private TeamService $team,
        private UserRepository $users,
    ) {
    }

    public function show(string $token, ?User $current): array
    {
        try {
            $invitation = $this->team->findInvitation($token);
        } catch (InvalidTokenException $exception) {
            return $this->invalid($token, $exception->getMessage());
        }

        return [
            'token' => $token,
            'error' => null,
            'invitation' => $this->invitation($invitation),
            'state' => $this->state((string) $invitation->getEmail(), $current),
            'loggedInAs' => $current?->getEmail(),
        ];
    }

    public function status(array $page): int
    {
        if ($page['invitation'] === null) {
            return self::STATUS_GONE;
        }

        return self::STATUS_OK;
    }

    private function invalid(string $token, string $error): array
    {
        return [
            'invitation' => null,
            'error' => $error,
            'token' => $token,
            'state' => null,
            'loggedInAs' => null,
        ];
    }

    private function invitation(CompanyInvitation $invitation): array
    {
        return [
            'company' => $invitation->getCompany()?->getName(),
            'establishment' => $invitation->getEstablishment()?->getName(),
            'email' => (string) $invitation->getEmail(),
            'roleLabel' => RoleHelper::label($this->role($invitation)),
            'invitedBy' => $invitation->getInvitedBy()?->getFirstName(),
        ];
    }

    private function role(CompanyInvitation $invitation): string
    {
        return $invitation->getRole()?->value
            ?? $invitation->getEstablishmentRole()?->value
            ?? EstablishmentRole::EMPLOYEE->value;
    }

    private function state(string $email, ?User $current): string
    {
        if ($current !== null && mb_strtolower((string) $current->getEmail()) === mb_strtolower($email)) {
            return 'ready';
        }

        if ($current !== null) {
            return 'other';
        }

        if ($this->users->emailExists($email)) {
            return 'login';
        }

        return 'signup';
    }
}