<?php

namespace App\Establishment\Enum;

enum SearchText
{
    case FREE_SLOT;
    case FREE_SLOTS;
    case NO_CITY;

    public static function results(int $count): self
    {
        if ($count > 1) {
            return self::FREE_SLOTS;
        }

        return self::FREE_SLOT;
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::FREE_SLOT => '%d créneau libre à %s',
            self::FREE_SLOTS => '%d créneaux libres à %s',
            self::NO_CITY => 'Aucune ville disponible',
        };
    }
}