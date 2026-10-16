<?php

namespace App\Shared\Helper;

class MoneyHelper
{
    public static function format(int $cents): string
    {
        $value = number_format($cents / 100, 2, ',', ' ');

        return (str_ends_with($value, ',00') ? substr($value, 0, -3) : $value) . ' €';
    }

    public static function formatExact(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ') . ' €';
    }

    public static function includingTax(int $cents, int $taxRate): int
    {
        return (int) round($cents * (100 + $taxRate) / 100);
    }

    public static function tax(int $cents, int $taxRate): int
    {
        return self::includingTax($cents, $taxRate) - $cents;
    }

    public static function applyDiscount(int $cents, int $percent): int
    {
        return (int) round($cents * (100 - max(0, min(100, $percent))) / 100);
    }
}