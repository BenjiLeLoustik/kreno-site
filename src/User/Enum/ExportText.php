<?php

namespace App\User\Enum;

enum ExportText
{
    case YES;
    case NO;
    case ME;
    case KRENO;
    case OTHER_AUTHOR;

    public static function yesNo(bool $value): string
    {
        if ($value) {
            return self::YES->label();
        }

        return self::NO->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::YES => 'oui',
            self::NO => 'non',
            self::ME => 'Moi',
            self::KRENO => 'Kréno',
            self::OTHER_AUTHOR => 'Établissement ou équipe Kréno',
        };
    }
}