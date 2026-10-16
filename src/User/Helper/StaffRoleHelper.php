<?php

namespace App\User\Helper;

use App\Entity\User\User;
use App\User\Enum\UserRole;

class StaffRoleHelper
{
    public const ROLES = [UserRole::ADMIN, UserRole::MODERATOR, UserRole::SUPPORT];

    public static function role(User $user): ?UserRole
    {
        $stored = $user->getStoredRoles();

        foreach (self::ROLES as $role) {
            if (in_array($role->value, $stored, true)) {
                return $role;
            }
        }

        return null;
    }

    public static function withRole(User $user, ?UserRole $role): array
    {
        $staff = array_map(static fn (UserRole $staffRole): string => $staffRole->value, self::ROLES);
        $roles = array_values(array_diff($user->getStoredRoles(), $staff));

        if ($role !== null) {
            $roles[] = $role->value;
        }

        return $roles;
    }

    public static function choices(): array
    {
        return array_map(static fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()], self::ROLES);
    }

    public static function signature(?User $user): string
    {
        if ($user === null) {
            return 'Kréno (automatique)';
        }

        $role = self::role($user);

        return $role !== null ? sprintf('%s (%s)', UserNameHelper::shortName($user), mb_strtolower($role->label())) : UserNameHelper::shortName($user);
    }
}