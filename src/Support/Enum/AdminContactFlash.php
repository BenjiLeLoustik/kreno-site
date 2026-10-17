<?php

namespace App\Support\Enum;

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
            self::REPLIED => 'Réponse envoyée à %s.',
            self::CLOSED => 'Demande fermée.',
            self::REOPENED => 'Demande rouverte.',
        };
    }
}