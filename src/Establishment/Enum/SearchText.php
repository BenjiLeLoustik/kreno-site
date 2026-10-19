<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

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
            self::FREE_SLOT => Translation::translate('%d créneau libre à %s'),
            self::FREE_SLOTS => Translation::translate('%d créneaux libres à %s'),
            self::NO_CITY => Translation::translate('Aucune ville disponible'),
        };
    }
}