<?php

declare(strict_types=1);

namespace App\Company\Enum;

enum MemberStatus: string
{
    case INVITED = 'invited';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::INVITED => 'Invitation envoyée',
            self::ACTIVE => 'Actif',
            self::DISABLED => 'Désactivé',
        };
    }
}