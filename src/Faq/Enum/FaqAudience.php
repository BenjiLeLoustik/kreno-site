<?php

namespace App\Faq\Enum;

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
            self::CLIENT => 'Clients',
            self::PRO => 'Professionnels',
        };
    }

    public function page(): string
    {
        return match ($this) {
            self::CLIENT => 'Page Aide',
            self::PRO => 'Page Pour les pros',
        };
    }
}