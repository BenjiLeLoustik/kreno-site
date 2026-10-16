<?php

namespace App\Establishment\Enum;

enum FollowLabel
{
    case FOLLOWING;
    case FOLLOW;

    public static function of(bool $following): self
    {
        if ($following) {
            return self::FOLLOWING;
        }

        return self::FOLLOW;
    }

    public function label(): string
    {
        return match ($this) {
            self::FOLLOWING => 'Salon suivi ✓',
            self::FOLLOW => 'Suivre ce salon',
        };
    }
}