<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum LegalDocument: string
{
    case CGU = 'cgu';
    case PRIVACY = 'privacy';
    case MENTIONS = 'mentions';
    case CGV = 'cgv';

    public const PATTERN = 'cgu|privacy|mentions|cgv';

    public const UPDATED_AT = '16 octobre 2026';

    public static function choices(self $selected): array
    {
        $choices = [];

        foreach (self::cases() as $document) {
            $choices[] = [
                'key' => $document->value,
                'label' => $document->label(),
                'selected' => $document === $selected,
            ];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::CGU => Translation::translate('Conditions d’utilisation'),
            self::PRIVACY => Translation::translate('Confidentialité'),
            self::MENTIONS => Translation::translate('Mentions légales'),
            self::CGV => Translation::translate('Conditions pros'),
        };
    }
}