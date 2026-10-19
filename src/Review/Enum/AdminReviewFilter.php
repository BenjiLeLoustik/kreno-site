<?php

namespace App\Review\Enum;

use App\Shared\Helper\Translation;

enum AdminReviewFilter: string
{
    case REPORTED = 'reported';
    case HIDDEN = 'hidden';
    case PUBLISHED = 'published';
    case ALL = 'all';

    public static function of(string $filter): self
    {
        return self::tryFrom($filter) ?? self::REPORTED;
    }

    public function label(): string
    {
        return match ($this) {
            self::REPORTED => Translation::translate('Signalés'),
            self::HIDDEN => Translation::translate('Masqués'),
            self::PUBLISHED => Translation::translate('Publiés'),
            self::ALL => Translation::translate('Tous'),
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::REPORTED => [ReviewStatus::REPORTED],
            self::HIDDEN => [ReviewStatus::HIDDEN],
            self::PUBLISHED => [ReviewStatus::PUBLISHED],
            self::ALL => ReviewStatus::cases(),
        };
    }
}