<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case SUPPORT = 'ROLE_SUPPORT';
    case MODERATOR = 'ROLE_MODERATOR';
    case ADMIN = 'ROLE_ADMIN';

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