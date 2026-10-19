<?php

namespace App\User\Helper;

use App\Entity\User\User;
use App\User\Enum\UserRole;
use App\User\Enum\UserText;

class StaffRoleHelper
{
    public static function role(User $user): ?UserRole
    {
        $stored = $user->getStoredRoles();

        foreach (UserRole::staff() as $role) {
            if (in_array($role->value, $stored, true)) {
                return $role;
            }
        }

        return null;
    }

    public static function withRole(User $user, ?UserRole $role): array
    {
        $roles = array_values(array_diff($user->getStoredRoles(), UserRole::staffValues()));

        if ($role !== null) {
            $roles[] = $role->value;
        }

        return $roles;
    }

    public static function choices(): array
    {
        return UserRole::staffChoices();
    }

    public static function signature(?User $user): string
    {
        if ($user === null) {
            return UserText::AUTOMATIC->text();
        }

        $name = UserNameHelper::shortName($user);
        $role = self::role($user);

        if ($role === null) {
            return $name;
        }

        return UserText::NAME_WITH_ROLE->text($name, mb_strtolower($role->label()));
    }
}