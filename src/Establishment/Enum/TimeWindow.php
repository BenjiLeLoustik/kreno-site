<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;
use DateTimeImmutable;

enum TimeWindow: string
{
    case NOW = 'now';
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case TOMORROW = 'tomorrow';
    case WEEK = 'week';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NOW => Translation::translate('Maintenant'),
            self::MORNING => Translation::translate('Ce matin'),
            self::AFTERNOON => Translation::translate('Cet après-midi'),
            self::EVENING => Translation::translate('Ce soir'),
            self::TOMORROW => Translation::translate('Demain'),
            self::WEEK => Translation::translate('Cette semaine'),
        };
    }

    public function range(DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);

        [$from, $to] = match ($this) {
            self::MORNING => [
                $today->setTime(6, 0),
                $today->setTime(12, 0)
            ],
            self::AFTERNOON => [
                $today->setTime(12, 0),
                $today->setTime(18, 0)
            ],
            self::EVENING => [
                $today->setTime(18, 0),
                $today->modify('+1 day')
            ],
            self::TOMORROW => [
                $today->modify('+1 day'),
                $today->modify('+2 days')
            ],
            self::WEEK => [
                $now,
                $today->modify('+7 days')
            ],
            self::NOW => [
                $now,
                null
            ],
        };

        return [
            max($from, $now),
            $to
        ];
    }
}