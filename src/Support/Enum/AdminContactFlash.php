<?php

namespace App\Support\Enum;

use App\Shared\Helper\Translation;

enum AdminContactFlash
{
    case REPLIED;
    case CLOSED;
    case REOPENED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::REPLIED => Translation::translate('Réponse envoyée à %s.'),
            self::CLOSED => Translation::translate('Demande fermée.'),
            self::REOPENED => Translation::translate('Demande rouverte.'),
        };
    }
}