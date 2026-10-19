<?php

namespace App\Report\Mapper;

use App\Report\Enum\AdminReportFilter;
use App\Report\Enum\ReportDirection;
use NeoPHP\Component\Http\Request\Request;

class AdminReportRequestMapper
{
    public static function filter(Request $request): AdminReportFilter
    {
        return AdminReportFilter::of($request->query->getString('status', AdminReportFilter::OPEN->value));
    }

    public static function direction(Request $request): ?ReportDirection
    {
        return ReportDirection::tryFrom($request->query->getString('direction'));
    }
}