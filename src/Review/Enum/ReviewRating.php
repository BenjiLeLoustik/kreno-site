<?php

namespace App\Review\Enum;

enum ReviewRating: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
    case FOUR = 4;
    case FIVE = 5;

    public static function values(): array
    {
        return array_map(static fn (self $rating): string => (string) $rating->value, self::cases());
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $rating) {
            $choices[] = ['key' => $rating->value, 'label' => $rating->label()];
        }

        return $choices;
    }

    public static function labelOf(int $rating): string
    {
        return self::tryFrom($rating)?->label() ?? '';
    }

    public function label(): string
    {
        return match ($this) {
            self::ONE => 'Décevant',
            self::TWO => 'Moyen',
            self::THREE => 'Bien',
            self::FOUR => 'Très bien',
            self::FIVE => 'Excellent',
        };
    }
}