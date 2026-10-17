<?php

namespace App\User\Enum;

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
            self::INVITED => 'Invitation envoyée à %s.',
            self::ROLE_CHANGED => 'Rôle modifié.',
            self::REVOKED => 'Accès retiré.',
        };
    }
}