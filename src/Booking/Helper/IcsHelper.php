<?php

namespace App\Booking\Helper;

use DateTimeImmutable;
use DateTimeZone;

class IcsHelper
{
    public static function event(string $uid, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt, string $summary, ?string $location = null, ?string $description = null): string
    {
        $utc = new DateTimeZone('UTC');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kreno//Reservations//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . (new DateTimeImmutable())->setTimezone($utc)->format('Ymd\THis\Z'),
            'DTSTART:' . $startsAt->setTimezone($utc)->format('Ymd\THis\Z'),
            'DTEND:' . $endsAt->setTimezone($utc)->format('Ymd\THis\Z'),
            'SUMMARY:' . self::escape($summary),
        ];

        if ($location !== null && $location !== '') {
            $lines[] = 'LOCATION:' . self::escape($location);
        }

        if ($description !== null && $description !== '') {
            $lines[] = 'DESCRIPTION:' . self::escape($description);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    public static function escape(string $value): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $value);
    }
}