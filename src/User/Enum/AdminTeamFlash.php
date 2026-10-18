<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum AdminTeamFlash
{
    case INVITED;
    case ROLE_CHANGED;
    case REVOKED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::INVITED => Translation::translate('Invitation envoyée à %s.'),
            self::ROLE_CHANGED => Translation::translate('Rôle modifié.'),
            self::REVOKED => Translation::translate('Accès retiré.'),
        };
    }
}