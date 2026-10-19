<?php

namespace App\Establishment\Mapper;

use App\Establishment\Dto\SlotInput;
use App\Establishment\Enum\SlotFilter;
use NeoPHP\Component\Http\Request\Request;

class SlotRequestMapper
{
    public static function filter(Request $request): SlotFilter
    {
        return SlotFilter::of($request->query->getString('filter', SlotFilter::ALL->value));
    }

    public static function sourceId(Request $request): int
    {
        return $request->query->getInt('from');
    }

    public static function serviceId(Request $request): ?int
    {
        return $request->query->getInt('serviceId') ?: null;
    }

    public static function day(Request $request): ?string
    {
        return $request->query->getString('day') ?: null;
    }

    public static function retry(SlotInput $input): array
    {
        return array_filter(['serviceId' => $input->serviceId, 'day' => $input->day]);
    }

    public static function next(SlotInput $input): array
    {
        return [
            'serviceId' => $input->serviceId,
            'day' => $input->day
        ];
    }

    public static function listQuery(Request $request): array
    {
        $filter = SlotFilter::tryFrom($request->request->getString('filter'));

        if ($filter === null || $filter === SlotFilter::ALL) {
            return [];
        }

        return [
            'filter' => $filter->value
        ];
    }
}