<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum UserText
{
    case AUTOMATIC;
    case NAME_WITH_ROLE;
    case SUSPENDED_UNTIL;

    public function text(string ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::AUTOMATIC => Translation::translate('Kréno (automatique)'),
            self::NAME_WITH_ROLE => '%s (%s)',
            self::SUSPENDED_UNTIL => Translation::translate('Compte suspendu jusqu\'au %s'),
        };
    }
}