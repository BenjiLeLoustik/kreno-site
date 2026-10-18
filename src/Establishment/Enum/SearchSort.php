<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SearchSort: string
{
    case SOONEST = 'soonest';
    case NEAREST = 'nearest';
    case DISCOUNT = 'discount';

    public static function values(): array
    {
        return array_map(static fn (self $sort): string => $sort->value, self::cases());
    }

    public static function choices(?string $selected): array
    {
        $choices = [];

        foreach (self::cases() as $sort) {
            $choices[] = [
                'key' => $sort->value,
                'label' => $sort->label(),
                'selected' => $sort->value === $selected
            ];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::SOONEST => Translation::translate('Le plus tôt'),
            self::NEAREST => Translation::translate('Le plus proche'),
            self::DISCOUNT => Translation::translate('La plus grosse remise'),
        };
    }
}