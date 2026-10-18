<?php

namespace App\Log\Enum;

use App\Shared\Helper\Translation;

enum ActivityFilter: string
{
    case ALL = 'all';
    case MODERATION = 'moderation';
    case SANCTION = 'sanction';
    case SETTINGS = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::ALL => Translation::translate('Tout'),
            self::MODERATION => Translation::translate('Modération'),
            self::SANCTION => Translation::translate('Sanctions'),
            self::SETTINGS => Translation::translate('Réglages'),
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