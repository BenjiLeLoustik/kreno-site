<?php

namespace App\Website\Enum;

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
            self::RULES_SAVED => 'Règles de la version %s enregistrées.',
        };
    }
}