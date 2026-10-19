<?php

namespace App\Faq\Enum;

use App\Shared\Helper\Translation;

enum FaqAudience: string
{
    case CLIENT = 'client';
    case PRO = 'pro';

    public static function values(): array
    {
        return array_map(static fn (self $audience): string => $audience->value, self::cases());
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $audience) {
            $choices[] = ['value' => $audience->value, 'label' => $audience->label()];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => Translation::translate('Clients'),
            self::PRO => Translation::translate('Professionnels'),
        };
    }

    public function page(): string
    {
        return match ($this) {
            self::CLIENT => Translation::translate('Page Aide'),
            self::PRO => Translation::translate('Page Pour les pros'),
        };
    }
}