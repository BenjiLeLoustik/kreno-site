<?php

namespace App\Establishment\Enum;

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
            self::UNFOLLOWED => 'Vous ne suivez plus %s.',
        };
    }
}