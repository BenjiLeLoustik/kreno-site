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

        if ($lastName === '') {
            return trim((string) $user->getFirstName());
        }

        return trim($user->getFirstName() . ' ' . mb_substr($lastName, 0, 1) . '.');
    }

    public static function initials(User $user): string
    {
        $first = mb_substr((string) $user->getFirstName(), 0, 1);
        $last = mb_substr((string) $user->getLastName(), 0, 1);

        return mb_strtoupper($first . $last);
    }
}