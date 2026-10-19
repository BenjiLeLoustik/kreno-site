<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

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
            self::YES => Translation::translate('oui'),
            self::NO => Translation::translate('non'),
            self::ME => Translation::translate('Moi'),
            self::KRENO => 'Kréno',
            self::OTHER_AUTHOR => Translation::translate('Établissement ou équipe Kréno'),
        };
    }
}