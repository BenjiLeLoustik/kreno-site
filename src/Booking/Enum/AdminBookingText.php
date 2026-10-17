<?php

namespace App\Booking\Enum;

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

    private static function plural(int $count): string
    {
        if ($count > 1) {
            return 's';
        }

        return '';
    }

    private function pattern(): string
    {
        return match ($this) {
            self::TODAY => 'Aujourd\'hui : %d réservation%s · %d annulation%s · %d absence%s',
        };
    }
}