<?php

namespace App\Company\Enum;

enum SignupRecapText
{
    case PAIR;
    case SERVICES;
    case DISCOUNT;
    case PAGE;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::PAIR => '%s · %s',
            self::SERVICES => '%d prestation%s%s',
            self::DISCOUNT => ' · remise −%d %%',
            self::PAGE => '%d photo%s · %d jour%s d\'ouverture',
        };
    }
}