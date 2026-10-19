<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportPriority: string
{
    case HIGH = 'high';
    case MID = 'mid';
    case LOW = 'low';

    public const SIMILAR_THRESHOLD = 2;

    public static function of(?ReportReason $reason, int $similar = 0): self
    {
        if ($reason === null) {
            return self::LOW;
        }

        if ($reason === ReportReason::CLOSED || $reason === ReportReason::BEHAVIOR) {
            return self::HIGH;
        }

        if ($similar >= self::SIMILAR_THRESHOLD) {
            return self::HIGH;
        }

        if ($reason === ReportReason::OTHER) {
            return self::LOW;
        }

        return self::MID;
    }

    public function label(): string
    {
        return match ($this) {
            self::HIGH => Translation::translate('Haute'),
            self::MID => Translation::translate('Moyenne'),
            self::LOW => Translation::translate('Basse'),
        };
    }
}