<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum SettingsFlash
{
    case RULES_SAVED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::RULES_SAVED => Translation::translate('Règles de la version %s enregistrées.'),
        };
    }
}