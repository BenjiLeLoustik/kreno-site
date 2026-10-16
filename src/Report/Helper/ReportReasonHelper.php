<?php

namespace App\Report\Helper;

use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportReason;

class ReportReasonHelper
{
    public static function allowed(ReportDirection $direction): array
    {
        return ReportReason::forDirection($direction);
    }

    public static function isAllowed(ReportDirection $direction, ReportReason $reason): bool
    {
        return in_array($reason, self::allowed($direction), true);
    }

    public static function choices(ReportDirection $direction, ?string $selected = null): array
    {
        $choices = [];

        foreach (self::allowed($direction) as $reason) {
            $choices[] = [
                'key' => $reason->value,
                'label' => $reason->label(),
                'hint' => $reason->hint(),
                'selected' => $reason->value === $selected,
            ];
        }

        return $choices;
    }
}