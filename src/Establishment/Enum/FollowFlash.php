<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum FollowFlash
{
    case UNFOLLOWED;

    public function text(string ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::UNFOLLOWED => Translation::translate('Vous ne suivez plus %s.'),
        };
    }
}