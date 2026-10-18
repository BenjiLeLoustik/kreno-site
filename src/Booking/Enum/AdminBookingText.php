<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

enum AdminBookingText
{
    case TODAY;

    public static function today(int $created, int $cancelled, int $noShows): string
    {
        return self::TODAY->text(
            $created,
            self::plural($created),
            $cancelled,
            self::plural($cancelled),
            $noShows,
            self::plural($noShows),
        );
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::TODAY => Translation::translate('Aujourd\'hui : %s · %s · %s'),
        };
    }

    private static function bookings(int $count): string
    {
        return Translation::translate(
            '{count, plural, one {# réservation} other {# réservations}}',
            [
                'count' => $count
            ],
        );
    }

    private static function cancellations(int $count): string
    {
        return Translation::translate(
            '{count, plural, one {# annulation} other {# annulations}}',
            [
                'count' => $count
            ],
        );
    }

    private static function absences(int $count): string
    {
        return Translation::translate(
            '{count, plural, one {# absence} other {# absences}}',
            [
                'count' => $count
            ],
        );
    }
}