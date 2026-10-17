<?php

namespace App\Company\Enum;

enum TeamFlash
{
    case INVITED;
    case INVITATION_REVOKED;
    case ROLE_CHANGED;
    case ESTABLISHMENT_ACCESS_REMOVED;
    case MEMBER_REMOVED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::INVITED => 'Invitation envoyée à %s.',
            self::INVITATION_REVOKED => 'Invitation annulée.',
            self::ROLE_CHANGED => 'Rôle modifié.',
            self::ESTABLISHMENT_ACCESS_REMOVED => 'Accès à %s retiré.',
            self::MEMBER_REMOVED => 'Membre retiré de l\'entreprise.',
        };
    }
}