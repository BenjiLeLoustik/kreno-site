<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum AdminUserFlash
{
    case SUSPENDED;
    case LIFTED;
    case BANNED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SUSPENDED => Translation::translate('Compte suspendu %d jours. L\'utilisateur a été prévenu par e-mail.'),
            self::LIFTED => Translation::translate('Compte réactivé.'),
            self::BANNED => Translation::translate('Compte banni.'),
        };
    }
}