<?php

namespace App\User\Mapper;

use App\User\Dto\AlertInput;
use NeoPHP\Component\Http\Request\Request;

class AlertRequestMapper
{
    public const DEFAULT_RADIUS_KM = 3;

    public static function alert(Request $request): AlertInput
    {
        $input = new AlertInput();
        $input->label = trim($request->request->getString('label')) ?: null;
        $input->citySlug = $request->request->getString('citySlug');
        $input->radiusKm = $request->request->getInt('radiusKm', self::DEFAULT_RADIUS_KM);
        $input->startTime = $request->request->getString('startTime');
        $input->endTime = $request->request->getString('endTime');
        $input->maxPrice = self::cents(trim($request->request->getString('maxPrice')));
        $input->categorySlugs = self::slugs($request->request->get('categorySlugs'));
        $input->enabled = $request->request->getBoolean('enabled');

        return $input;
    }

    private static function cents(string $price): int
    {
        if ($price === '' || !is_numeric($price)) {
            return 0;
        }

        return (int) round((float) $price * 100);
    }

    private static function slugs(mixed $slugs): array
    {
        if (!is_array($slugs)) {
            return [];
        }

        return array_values(array_filter($slugs, 'is_string'));
    }
}