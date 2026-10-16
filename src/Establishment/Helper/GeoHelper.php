<?php

namespace App\Establishment\Helper;

class GeoHelper
{
    public const EARTH_RADIUS_KM = 6371.0;

    public static function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function between(?string $fromLatitude, ?string $fromLongitude, ?string $toLatitude, ?string $toLongitude): ?float
    {
        if ($fromLatitude === null || $fromLongitude === null || $toLatitude === null || $toLongitude === null) {
            return null;
        }

        return self::distanceKm((float) $fromLatitude, (float) $fromLongitude, (float) $toLatitude, (float) $toLongitude);
    }

    public static function format(?float $kilometers): ?string
    {
        if ($kilometers === null) {
            return null;
        }

        if ($kilometers < 1) {
            return max(50, (int) (round($kilometers * 1000 / 50) * 50)) . ' m';
        }

        $value = number_format($kilometers, 1, ',', ' ');

        return (str_ends_with($value, ',0') ? substr($value, 0, -2) : $value) . ' km';
    }
}