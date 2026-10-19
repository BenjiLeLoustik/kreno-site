<?php

namespace App\Report\Enum;

use App\Repository\Report\ReportRepository;
use App\Shared\Helper\Translation;

enum AdminReportFilter: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ALL = 'all';

    public static function of(string $filter): self
    {
        return self::tryFrom($filter) ?? self::OPEN;
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $filter) {
            $choices[] = [
                'key' => $filter->value,
                'label' => $filter->label()
            ];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN => Translation::translate('Ouverts'),
            self::CLOSED => Translation::translate('Clos'),
            self::ALL => Translation::translate('Tous'),
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::OPEN => ReportRepository::OPEN_STATUSES,
            self::CLOSED => ReportRepository::CLOSED_STATUSES,
            self::ALL => [...ReportRepository::OPEN_STATUSES, ...ReportRepository::CLOSED_STATUSES],
        };
    }
}