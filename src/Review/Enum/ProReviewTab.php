<?php

namespace App\Review\Enum;

enum ProReviewTab: string
{
    case ALL = 'all';
    case UNANSWERED = 'unanswered';
    case REPORTED = 'reported';

    public static function of(string $tab): self
    {
        return self::tryFrom($tab) ?? self::ALL;
    }

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Tous',
            self::UNANSWERED => 'Sans réponse',
            self::REPORTED => 'Signalés',
        };
    }

    public function criteria(): array
    {
        return match ($this) {
            self::ALL => [],
            self::UNANSWERED => ['reply' => null, 'status' => ReviewStatus::visible()],
            self::REPORTED => ['status' => ReviewStatus::REPORTED],
        };
    }
}