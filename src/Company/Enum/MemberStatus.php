<?php

declare(strict_types=1);

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum MemberStatus: string
{
    case INVITED = 'invited';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::INVITED => Translation::translate('Invitation envoyée'),
            self::ACTIVE => Translation::translate('Actif'),
            self::DISABLED => Translation::translate('Désactivé'),
        };
    }
}