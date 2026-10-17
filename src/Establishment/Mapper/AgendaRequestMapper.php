<?php

namespace App\Establishment\Mapper;

use NeoPHP\Component\Http\Request\Request;

class AgendaRequestMapper
{
    public static function week(Request $request): ?string
    {
        return $request->query->getString('week') ?: null;
    }
}