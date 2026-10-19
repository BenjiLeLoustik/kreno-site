<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\User\UserRepository;
use App\User\Dto\StaffInviteInput;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\User\Enum\UserTokenType;
use App\User\Event\StaffInvitedEvent;
use App\User\Exception\AlreadyStaffException;
use App\User\Exception\InactiveAccountException;
use App\User\Exception\InvalidStaffRoleException;
use App\User\Exception\LastAdminException;
use App\User\Exception\NotStaffMemberException;
use App\User\Exception\SelfAccessChangeException;
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
        $members = [];

        foreach ($this->users->findStaff() as $user) {
            if (StaffRoleHelper::role($user) !== null && $user->getStatus() !== UserStatus::DELETED) {
                $members[] = $user;
            }
        }

        return $members;
    }

    public function invite(StaffInviteInput $input, User $actor): User
    {
        $email = mb_strtolower(trim((string) $input->email));
        $role = UserRole::from((string) $input->role);
        $user = $this->users->findOneByEmail($email);
        $token = null;

        if ($user !== null) {
            $this->assertInvitable($user, $email);
        }

        if ($user === null) {
            $user = $this->createUser($email, $input);
            $token = $this->tokens->create($user, UserTokenType::ACCOUNT_SETUP);
        }

        $user->setRoles(StaffRoleHelper::withRole($user, $role));
        $message = sprintf('%s invité(e) dans l\'équipe (%s)', UserNameHelper::shortName($user), $role->label());
        $this->log($user, $actor, $message, null, $role);
        $this->orm->flush();

        $this->events->dispatch(new StaffInvitedEvent($user, $role, $actor, $token));

        return $user;
    }

    public function changeRole(User $member, UserRole $role, User $actor): User
    {
        $current = $this->assertMember($member);
        $this->assertNotSelf($member, $actor);

        if (!$role->isStaff()) {
            throw new InvalidStaffRoleException();
        }

        if ($current === $role) {
            return $member;
        }

        if ($current === UserRole::ADMIN) {
            $this->assertNotLastAdmin();
        }

        $member->setRoles(StaffRoleHelper::withRole($member, $role));
        $message = sprintf('%s : %s → %s', UserNameHelper::shortName($member), $current->label(), $role->label());
        $this->log($member, $actor, $message, $current, $role);
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
        $message = sprintf('%s retiré(e) de l\'équipe', UserNameHelper::shortName($member));
        $this->log($member, $actor, $message, $current, null);
        $this->orm->flush();

        return $member;
    }

    private function assertInvitable(User $user, string $email): void
    {
        if (StaffRoleHelper::role($user) !== null) {
            throw new AlreadyStaffException($email);
        }

        if ($user->getStatus() !== UserStatus::ACTIVE) {
            throw new InactiveAccountException($email);
        }
    }

    private function createUser(string $email, StaffInviteInput $input): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName(trim((string) $input->firstName))
            ->setLastName($this->optional($input->lastName))
            ->setRoles([UserRole::USER->value]);

        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(24))));
        $this->orm->persist($user);

        return $user;
    }

    private function optional(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function log(User $member, User $actor, string $message, ?UserRole $from, ?UserRole $to): void
    {
        $context = ['role' => ['from' => $from?->value, 'to' => $to?->value]];

        $this->logger->log(ActivityType::TEAM, $message, $actor, null, $member, $context);
    }

    private function assertMember(User $member): UserRole
    {
        $role = StaffRoleHelper::role($member);

        if ($role === null) {
            throw new NotStaffMemberException();
        }

        return $role;
    }

    private function assertNotSelf(User $member, User $actor): void
    {
        if ($member === $actor) {
            throw new SelfAccessChangeException();
        }
    }

    private function assertNotLastAdmin(): void
    {
        $admins = 0;

        foreach ($this->members() as $user) {
            if (StaffRoleHelper::role($user) === UserRole::ADMIN && $user->getStatus() === UserStatus::ACTIVE) {
                $admins++;
            }
        }

        if ($admins <= 1) {
            throw new LastAdminException();
        }
    }
}