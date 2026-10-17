<?php

namespace App\Establishment\Enum;

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
            self::SUSPENDED => '%s est suspendu. Le pro a été prévenu par e-mail.',
            self::RESUMED => '%s est de nouveau en ligne.',
        };
    }
}