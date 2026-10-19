<?php

namespace App\Establishment\Mapper;

use App\Establishment\Dto\ServiceInput;
use App\Establishment\Dto\ServicesInput;
use NeoPHP\Component\Http\Request\Request;

class ServiceRequestMapper
{
    public static function service(Request $request): ServiceInput
    {
        $input = new ServiceInput();
        $input->name = trim($request->request->getString('name')) ?: null;
        $input->description = trim($request->request->getString('description')) ?: null;
        $input->price = self::cents($request->request->getString('price'));
        $input->duration = $request->request->getInt('duration') ?: null;
        $input->defaultDiscount = self::discount($request);

        return $input;
    }

    public static function services(Request $request): ServicesInput
    {
        $input = new ServicesInput();
        $input->defaultDiscount = self::discount($request);
        $rows = $request->request->get('services');

        if (!is_array($rows)) {
            return $input;
        }

        foreach ($rows as $row) {
            if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '') {
                $input->services[] = self::row($row);
            }
        }

        return $input;
    }

    private static function row(array $row): ServiceInput
    {
        $service = new ServiceInput();
        $service->name = trim((string) $row['name']);
        $service->price = self::cents((string) ($row['price'] ?? ''));
        $service->duration = (int) ($row['duration'] ?? 0) ?: null;
        $service->active = true;

        return $service;
    }

    private static function cents(string $value): ?int
    {
        $price = str_replace([' ', ','], ['', '.'], $value);

        if (!is_numeric($price)) {
            return null;
        }

        return (int) round((float) $price * 100);
    }

    private static function discount(Request $request): ?int
    {
        $discount = $request->request->getString('defaultDiscount');

        if ($discount === '') {
            return null;
        }

        return (int) $discount;
    }
}