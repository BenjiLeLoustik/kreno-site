<?php

namespace App\Establishment\Mapper;

use App\Establishment\Enum\AdminEstablishmentFilter;
use NeoPHP\Component\Http\Request\Request;

class AdminEstablishmentRequestMapper
{
    public static function filter(Request $request): AdminEstablishmentFilter
    {
        return AdminEstablishmentFilter::of($request->query->getString('status', AdminEstablishmentFilter::ALL->value));
    }
}