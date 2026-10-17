<?php

namespace App\Report\Mapper;

use NeoPHP\Component\Http\Request\Request;

class ProReportRequestMapper
{
    public static function tab(Request $request): string
    {
        return $request->query->getString('tab');
    }

    public static function reason(Request $request): ?string
    {
        return $request->query->getString('reason') ?: null;
    }

    public static function bookingId(Request $request): ?int
    {
        return $request->query->getInt('booking') ?: null;
    }
}