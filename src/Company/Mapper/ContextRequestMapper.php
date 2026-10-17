<?php

namespace App\Company\Mapper;

use NeoPHP\Component\Http\Request\Request;

class ContextRequestMapper
{
    public const PRO_PREFIX = '/pro';

    public static function target(Request $request): ?string
    {
        $target = $request->request->getString('_target');

        if (!str_starts_with($target, self::PRO_PREFIX) || str_starts_with($target, '//')) {
            return null;
        }

        return $target;
    }
}