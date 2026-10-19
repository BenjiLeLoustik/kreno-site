<?php

namespace App\Log\Mapper;

use App\Shared\Mapper\AdminQueryMapper;
use NeoPHP\Component\Http\Request\Request;

class ActivityJournalRequestMapper
{
    public static function query(Request $request): array
    {
        return [
            'group' => AdminQueryMapper::optional($request, 'group'),
            'search' => AdminQueryMapper::search($request),
            'page' => AdminQueryMapper::page($request),
        ];
    }
}