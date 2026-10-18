<?php

declare(strict_types=1);

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => Translation::translate('Actif'),
            self::SUSPENDED => Translation::translate('Suspendu'),
            self::BANNED => Translation::translate('Banni'),
            self::DELETED => Translation::translate('Supprimé'),
        };
    }
}