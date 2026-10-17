<?php

namespace App\Review\Enum;

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
            self::REPORTED => 'Signalés',
            self::HIDDEN => 'Masqués',
            self::PUBLISHED => 'Publiés',
            self::ALL => 'Tous',
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