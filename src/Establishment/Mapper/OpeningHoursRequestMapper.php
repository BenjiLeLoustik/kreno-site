<?php

namespace App\Establishment\Mapper;

use App\Establishment\Dto\OpeningHourInput;
use App\Establishment\Dto\OpeningHoursInput;
use NeoPHP\Component\Http\Request\Request;

class OpeningHoursRequestMapper
{
    public static function openingHours(Request $request): OpeningHoursInput
    {
        $input = new OpeningHoursInput();
        $input->hours = self::hours($request->request->get('hours'));

        return $input;
    }

    public static function hours(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $hours = [];

        foreach ($rows as $row) {
            if (self::filled($row)) {
                $hours[] = self::hour($row);
            }
        }

        return $hours;
    }

    private static function filled(mixed $row): bool
    {
        return is_array($row) && ($row['opensAt'] ?? '') !== '' && ($row['closesAt'] ?? '') !== '';
    }

    private static function hour(array $row): OpeningHourInput
    {
        $hour = new OpeningHourInput();
        $hour->weekday = (int) ($row['weekday'] ?? 0);
        $hour->opensAt = (string) $row['opensAt'];
        $hour->closesAt = (string) $row['closesAt'];

        return $hour;
    }
}