<?php

namespace App\User\Security;

use App\Entity\User\User;
use App\User\Enum\UserStatus;
use NeoPHP\Package\Security\Contract\UserCheckerInterface;
use NeoPHP\Package\Security\Contract\UserInterface;
use NeoPHP\Package\Security\Exception\CustomUserMessageAuthenticationException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === UserStatus::DELETED) {
            throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
        }

        if ($user->getStatus() === UserStatus::BANNED) {
            throw new CustomUserMessageAuthenticationException('Votre compte a été fermé. Contactez aide@kreno.fr pour en savoir plus.');
        }
    }

    public function checkPostAuth(
        UserInterface $user
    ): void {
    }
}