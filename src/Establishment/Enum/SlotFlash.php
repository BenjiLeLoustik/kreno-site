<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SlotFlash
{
    case PUBLISHED;
    case WITHDRAWN;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::PUBLISHED => Translation::translate('Votre créneau de %s est en ligne. Il disparaîtra à %s s\'il n\'est pas réservé.'),
            self::WITHDRAWN => Translation::translate('Le créneau de %s est retiré.'),
        };
    }
}