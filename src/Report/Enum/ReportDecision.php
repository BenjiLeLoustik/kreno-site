<?php

namespace App\Report\Enum;

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
            self::DISMISS => 'Classé sans suite',
            self::WARN => 'Avertissement envoyé',
            self::SUSPEND => $this->suspension($direction, $days),
            self::RESOLVE => 'Traité',
        };
    }

    private function suspension(?ReportDirection $direction, int $days): string
    {
        if ($direction === ReportDirection::FROM_CUSTOMER) {
            return 'Établissement suspendu';
        }

        return sprintf('Compte client suspendu %d jours', $days);
    }
}