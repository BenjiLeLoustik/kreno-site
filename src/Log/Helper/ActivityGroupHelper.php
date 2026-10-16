<?php

namespace App\Log\Helper;

use App\Log\Enum\ActivityType;

class ActivityGroupHelper
{
    public const ALL = 'all';

    public const GROUPS = [
        'moderation' => 'Modération',
        'sanction' => 'Sanctions',
        'settings' => 'Réglages',
    ];

    public const BADGES = [
        'moderation' => 'Modération',
        'sanction' => 'Sanction',
        'settings' => 'Réglage',
        'billing' => 'Facturation',
    ];

    public static function group(ActivityType $type): string
    {
        return match ($type) {
            ActivityType::MODERATION => 'moderation',
            ActivityType::SANCTION => 'sanction',
            ActivityType::BILLING => 'billing',
            default => 'settings',
        };
    }

    public static function types(string $group): array
    {
        return array_values(array_filter(ActivityType::cases(), static fn (ActivityType $type): bool => $group === self::ALL || self::group($type) === $group));
    }

    public static function normalize(?string $group): string
    {
        return $group !== null && isset(self::GROUPS[$group]) ? $group : self::ALL;
    }

    public static function filters(string $selected): array
    {
        $filters = [['key' => self::ALL, 'label' => 'Tout', 'selected' => $selected === self::ALL]];

        foreach (self::GROUPS as $key => $label) {
            $filters[] = ['key' => $key, 'label' => $label, 'selected' => $selected === $key];
        }

        return $filters;
    }
}