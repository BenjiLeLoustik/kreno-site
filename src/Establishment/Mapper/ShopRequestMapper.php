<?php

namespace App\Establishment\Mapper;

use NeoPHP\Component\Http\Request\Request;

class ShopRequestMapper
{
    public static function origin(Request $request): array
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return [null, null];
        }

        return [max(-90.0, min(90.0, (float) $latitude)), max(-180.0, min(180.0, (float) $longitude))];
    }
}