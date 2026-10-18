<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

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
            self::FOLLOWING => Translation::translate('Salon suivi ✓'),
            self::FOLLOW => Translation::translate('Suivre ce salon'),
        };
    }
}