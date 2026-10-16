<?php

namespace App\Log\Enum;

enum ActivityFilter: string
{
    case ALL = 'all';
    case MODERATION = 'moderation';
    case SANCTION = 'sanction';
    case SETTINGS = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Tout',
            self::MODERATION => 'Modération',
            self::SANCTION => 'Sanctions',
            self::SETTINGS => 'Réglages',
        };
    }

    public function accepts(ActivityType $type): bool
    {
        if ($this === self::ALL) {
            return true;
        }

        return ActivityGroup::of($type)->value === $this->value;
    }
}