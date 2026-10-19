<?php

namespace App\User\Security;

use App\Entity\User\User;
use App\User\Enum\UserStatus;
use App\User\Exception\AccountClosedException;
use App\User\Exception\InvalidCredentialsException;
use NeoPHP\Package\Security\Contract\UserCheckerInterface;
use NeoPHP\Package\Security\Contract\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === UserStatus::DELETED) {
            throw new InvalidCredentialsException();
        }

        if ($user->getStatus() === UserStatus::BANNED) {
            throw new AccountClosedException();
        }
    }

    public function checkPostAuth(
        UserInterface $user
    ): void {
    }
}