<?php

namespace App\User\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case SUPPORT = 'ROLE_SUPPORT';
    case MODERATOR = 'ROLE_MODERATOR';
    case ADMIN = 'ROLE_ADMIN';

    public static function staff(): array
    {
        return [self::ADMIN, self::MODERATOR, self::SUPPORT];
    }

    public static function staffValues(): array
    {
        $values = [];

        foreach (self::staff() as $role) {
            $values[] = $role->value;
        }

        return $values;
    }

    public static function staffChoices(): array
    {
        $choices = [];

        foreach (self::staff() as $role) {
            $choices[] = ['value' => $role->value, 'label' => $role->label()];
        }

        return $choices;
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::USER => 'Client',
            self::SUPPORT => 'Support',
            self::MODERATOR => 'Modération',
            self::ADMIN => 'Administrateur',
        };
    }
}