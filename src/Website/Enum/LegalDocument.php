<?php

namespace App\Website\Enum;

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
            self::CGU => 'Conditions d’utilisation',
            self::PRIVACY => 'Confidentialité',
            self::MENTIONS => 'Mentions légales',
            self::CGV => 'Conditions pros',
        };
    }
}