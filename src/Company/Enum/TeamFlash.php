<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

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
            self::INVITED => Translation::translate('Invitation envoyée à %s.'),
            self::INVITATION_REVOKED => Translation::translate('Invitation annulée.'),
            self::ROLE_CHANGED => Translation::translate('Rôle modifié.'),
            self::ESTABLISHMENT_ACCESS_REMOVED => Translation::translate('Accès à %s retiré.'),
            self::MEMBER_REMOVED => Translation::translate('Membre retiré de l\'entreprise.'),
        };
    }
}