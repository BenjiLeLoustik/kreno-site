<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportDecision: string
{
    case DISMISS = 'dismiss';
    case WARN = 'warn';
    case SUSPEND = 'suspend';
    case RESOLVE = 'resolve';

    public static function values(): array
    {
        $values = [];

        foreach (self::cases() as $decision) {
            $values[] = $decision->value;
        }

        return $values;
    }

    public function notifiesEstablishment(): bool
    {
        return $this !== self::DISMISS;
    }

    public function status(): ReportStatus
    {
        if ($this === self::DISMISS) {
            return ReportStatus::DISMISSED;
        }

        return ReportStatus::RESOLVED;
    }

    public function resolution(?ReportDirection $direction, int $days): string
    {
        return match ($this) {
            self::DISMISS => Translation::translate('Classé sans suite'),
            self::WARN => Translation::translate('Avertissement envoyé'),
            self::SUSPEND => $this->suspension($direction, $days),
            self::RESOLVE => Translation::translate('Traité'),
        };
    }

    private function suspension(?ReportDirection $direction, int $days): string
    {
        if ($direction === ReportDirection::FROM_CUSTOMER) {
            return Translation::translate('Établissement suspendu');
        }

        return sprintf(Translation::translate('Compte client suspendu %d jours'), $days);
    }
}