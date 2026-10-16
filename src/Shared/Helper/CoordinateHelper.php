<?php

namespace App\Shared\Helper;

class CoordinateHelper
{
    public const DECIMALS = 7;

    public static function format(float $value): string
    {
        return number_format($value, self::DECIMALS, '.', '');
    }
}