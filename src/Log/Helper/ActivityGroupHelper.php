<?php

namespace App\Log\Helper;

use App\Log\Enum\ActivityFilter;
use App\Log\Enum\ActivityType;

class ActivityGroupHelper
{
    public const ALL = 'all';

    public static function types(string $group): array
    {
        $filter = ActivityFilter::from(self::normalize($group));
        $types = [];

        foreach (ActivityType::cases() as $type) {
            if ($filter->accepts($type)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public static function normalize(?string $group): string
    {
        return (ActivityFilter::tryFrom((string) $group) ?? ActivityFilter::ALL)->value;
    }

    public static function filters(string $selected): array
    {
        $filters = [];

        foreach (ActivityFilter::cases() as $filter) {
            $filters[] = [
                'key' => $filter->value,
                'label' => $filter->label(),
                'selected' => $selected === $filter->value,
            ];
        }

        return $filters;
    }
}