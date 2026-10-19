<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum AdminEstablishmentFlash
{
    case SUSPENDED;
    case RESUMED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SUSPENDED => Translation::translate('%s est suspendu. Le pro a été prévenu par e-mail.'),
            self::RESUMED => Translation::translate('%s est de nouveau en ligne.'),
        };
    }
}