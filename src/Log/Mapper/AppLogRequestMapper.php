<?php

namespace App\Log\Mapper;

use App\Log\Enum\AppLogStatus;
use NeoPHP\Component\Http\Request\Request;

class AppLogRequestMapper
{
    public static function page(Request $request): int
    {
        return max(1, $request->query->getInt('page', 1));
    }

    public static function filterStatus(Request $request): ?AppLogStatus
    {
        if (!$request->query->has('status')) {
            return AppLogStatus::NEW;
        }

        return AppLogStatus::tryFrom($request->query->getString('status'));
    }

    public static function search(Request $request): string
    {
        return trim($request->query->getString('q'));
    }

    public static function status(Request $request): ?AppLogStatus
    {
        return AppLogStatus::tryFrom($request->request->getString('status'));
    }

    public static function note(Request $request): string
    {
        return $request->request->getString('note');
    }

    public static function returnQuery(Request $request): array
    {
        $query = [
            'status' => $request->request->getString('filterStatus', AppLogStatus::NEW->value),
            'q' => trim($request->request->getString('filterQ')),
        ];

        return array_filter($query, static fn (string $value): bool => $value !== '');
    }
}