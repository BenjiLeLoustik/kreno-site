<?php

namespace App\Billing\Mapper;

use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;

class AdminBillingRequestMapper
{
    public const MONTH_FORMAT = '!Y-m';

    public static function month(Request $request): ?DateTimeImmutable
    {
        $month = DateTimeImmutable::createFromFormat(self::MONTH_FORMAT, $request->query->getString('month'));

        if ($month === false) {
            return null;
        }

        return $month;
    }
}