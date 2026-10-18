<?php

namespace App\Review\Enum;

use App\Shared\Helper\Translation;

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
            self::ALL => Translation::translate('Tous'),
            self::UNANSWERED => Translation::translate('Sans réponse'),
            self::REPORTED => Translation::translate('Signalés'),
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