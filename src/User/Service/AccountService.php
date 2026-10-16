<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\User\Dto\ProfileInput;
use App\User\Dto\SettingsInput;
use App\User\Enum\UserStatus;
use App\User\Event\AccountDeletedEvent;
use App\User\Exception\EmailAlreadyUsedException;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AccountService
{
    public const DELETED_EMAIL = 'supprime-%d@kreno.invalid';

    public const DELETED_FIRST_NAME = 'Compte supprimé';

    public function __construct(
        private OrmManagerInterface $orm,
        private UserRepository $users,
        private EventManagerInterface $events,
    ) {
    }

    public function updateProfile(User $user, ProfileInput $input): User
    {
        if ($input->firstName !== null && trim($input->firstName) !== '') {
            $user->setFirstName(trim($input->firstName));
        }

        if ($input->lastName !== null) {
            $user->setLastName(trim($input->lastName) ?: null);
        }

        if ($input->phone !== null) {
            $user->setPhone(trim($input->phone) ?: null);
        }

        if ($input->email !== null) {
            $this->changeEmail($user, $input->email);
        }

        $this->orm->flush();

        return $user;
    }

    public function updateSettings(User $user, SettingsInput $input): User
    {
        if ($input->emailNotifications !== null) {
            $user->setEmailNotifications($input->emailNotifications);
        }

        $this->orm->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $email = (string) $user->getEmail();
        $firstName = (string) $user->getFirstName();

        $this->events->dispatch(new AccountDeletedEvent($user, $email, $firstName));

        foreach ($user->getAlerts()->toArray() as $alert) {
            $user->removeAlert($alert);
        }

        foreach ($user->getFollows()->toArray() as $follow) {
            $user->removeFollow($follow);
        }

        $this->anonymize($user);
        $this->orm->flush();
    }

    private function changeEmail(User $user, string $email): void
    {
        $email = mb_strtolower(trim($email));

        if ($email === $user->getEmail()) {
            return;
        }

        if ($this->users->emailExists($email)) {
            throw new EmailAlreadyUsedException();
        }

        $user->setEmail($email)->setEmailVerifiedAt(null);
    }

    private function anonymize(User $user): void
    {
        $user
            ->setStatus(UserStatus::DELETED)
            ->setEmail(sprintf(self::DELETED_EMAIL, $user->getId()))
            ->setFirstName(self::DELETED_FIRST_NAME)
            ->setLastName(null)
            ->setPhone(null)
            ->setPassword(bin2hex(random_bytes(32)))
            ->setEmailNotifications(false)
            ->setDeletedAt(new DateTimeImmutable());
    }
}