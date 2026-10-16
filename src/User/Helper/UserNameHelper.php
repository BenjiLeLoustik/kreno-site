<?php

namespace App\User\Helper;

use App\Entity\User\User;

class UserNameHelper
{
    public static function fullName(User $user): string
    {
        return trim($user->getFirstName() . ' ' . $user->getLastName());
    }

    public static function shortName(User $user): string
    {
        $lastName = (string) $user->getLastName();

        return trim($user->getFirstName() . ($lastName !== '' ? ' ' . mb_substr($lastName, 0, 1) . '.' : ''));
    }

    public static function initials(User $user): string
    {
        return mb_strtoupper(mb_substr((string) $user->getFirstName(), 0, 1) . mb_substr((string) $user->getLastName(), 0, 1));
    }
}