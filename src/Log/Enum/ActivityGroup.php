<?php

namespace App\Log\Enum;

enum ActivityGroup: string
{
    case MODERATION = 'moderation';
    case SANCTION = 'sanction';
    case SETTINGS = 'settings';
    case BILLING = 'billing';

    public static function of(?ActivityType $type): self
    {
        return match ($type) {
            ActivityType::MODERATION => self::MODERATION,
            ActivityType::SANCTION => self::SANCTION,
            ActivityType::BILLING => self::BILLING,
            default => self::SETTINGS,
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::MODERATION => 'Modération',
            self::SANCTION => 'Sanction',
            self::SETTINGS => 'Réglage',
            self::BILLING => 'Facturation',
        };
    }
}