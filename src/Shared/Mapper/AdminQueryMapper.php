<?php

namespace App\Shared\Mapper;

use NeoPHP\Component\Http\Request\Request;

class AdminQueryMapper
{
    public static function page(Request $request): int
    {
        return max(1, $request->query->getInt('page', 1));
    }

    public static function search(Request $request): string
    {
        return trim($request->query->getString('q'));
    }

    public static function optional(Request $request, string $key): ?string
    {
        return $request->query->getString($key) ?: null;
    }

    public static function returnQuery(Request $request, string $defaultStatus): array
    {
        $query = [
            'status' => $request->request->getString('filterStatus', $defaultStatus),
            'q' => trim($request->request->getString('filterQ')),
        ];

        return array_filter($query, static fn (string $value): bool => $value !== '');
    }

    public static function nullable(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }
}