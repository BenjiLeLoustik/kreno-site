<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Dto\StaffInviteInput;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\User\Enum\UserTokenType;
use App\User\Event\StaffInvitedEvent;
use App\User\Helper\StaffRoleHelper;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class StaffService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private UserPasswordHasher $hasher,
        private UserRepository $users,
        private UserTokenService $tokens,
        private ActivityLogger $logger,
    ) {
    }

    public function members(): array
    {
        return array_values(array_filter($this->users->findStaff(), static fn (User $user): bool => StaffRoleHelper::role($user) !== null && $user->getStatus() !== UserStatus::DELETED));
    }

    public function invite(StaffInviteInput $input, User $actor): User
    {
        $email = mb_strtolower(trim((string) $input->email));
        $role = UserRole::from((string) $input->role);
        $user = $this->users->findOneByEmail($email);
        $token = null;

        if ($user !== null && StaffRoleHelper::role($user) !== null) {
            throw new BusinessRuleException('{email} fait déjà partie de l\'équipe Kréno.', ['email' => $email]);
        }

        if ($user !== null && $user->getStatus() !== UserStatus::ACTIVE) {
            throw new BusinessRuleException('Le compte {email} n\'est pas actif : il ne peut pas rejoindre l\'équipe.', ['email' => $email]);
        }

        if ($user === null) {
            $lastName = trim((string) $input->lastName);
            $user = (new User())
                ->setEmail($email)
                ->setFirstName(trim((string) $input->firstName))
                ->setLastName($lastName !== '' ? $lastName : null)
                ->setRoles([UserRole::USER->value]);

            $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(24))));
            $this->orm->persist($user);
            $token = $this->tokens->create($user, UserTokenType::ACCOUNT_SETUP);
        }

        $user->setRoles(StaffRoleHelper::withRole($user, $role));
        $this->logger->log(ActivityType::TEAM, sprintf('%s invité(e) dans l\'équipe (%s)', UserNameHelper::shortName($user), $role->label()), $actor, null, $user, ['role' => ['from' => null, 'to' => $role->value]]);
        $this->orm->flush();

        $this->events->dispatch(new StaffInvitedEvent($user, $role, $actor, $token));

        return $user;
    }

    public function changeRole(User $member, UserRole $role, User $actor): User
    {
        $current = $this->assertMember($member);
        $this->assertNotSelf($member, $actor);

        if (!in_array($role, StaffRoleHelper::ROLES, true)) {
            throw new BusinessRuleException('Ce rôle n\'existe pas pour l\'équipe Kréno.');
        }

        if ($current === $role) {
            return $member;
        }

        if ($current === UserRole::ADMIN) {
            $this->assertNotLastAdmin();
        }

        $member->setRoles(StaffRoleHelper::withRole($member, $role));
        $this->logger->log(ActivityType::TEAM, sprintf('%s : %s → %s', UserNameHelper::shortName($member), $current->label(), $role->label()), $actor, null, $member, ['role' => ['from' => $current->value, 'to' => $role->value]]);
        $this->orm->flush();

        return $member;
    }

    public function revoke(User $member, User $actor): User
    {
        $current = $this->assertMember($member);
        $this->assertNotSelf($member, $actor);

        if ($current === UserRole::ADMIN) {
            $this->assertNotLastAdmin();
        }

        $member->setRoles(StaffRoleHelper::withRole($member, null));
        $this->logger->log(ActivityType::TEAM, sprintf('%s retiré(e) de l\'équipe', UserNameHelper::shortName($member)), $actor, null, $member, ['role' => ['from' => $current->value, 'to' => null]]);
        $this->orm->flush();

        return $member;
    }

    private function assertMember(User $member): UserRole
    {
        return StaffRoleHelper::role($member) ?? throw new BusinessRuleException('Cette personne ne fait pas partie de l\'équipe Kréno.');
    }

    private function assertNotSelf(User $member, User $actor): void
    {
        if ($member === $actor) {
            throw new BusinessRuleException('Vous ne pouvez pas modifier votre propre accès.');
        }
    }

    private function assertNotLastAdmin(): void
    {
        $admins = array_filter($this->members(), static fn (User $user): bool => StaffRoleHelper::role($user) === UserRole::ADMIN && $user->getStatus() === UserStatus::ACTIVE);

        if (count($admins) <= 1) {
            throw new BusinessRuleException('L\'équipe doit garder au moins un administrateur.');
        }
    }
}