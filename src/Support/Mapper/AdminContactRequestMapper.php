<?php

namespace App\Support\Mapper;

use App\Shared\Mapper\AdminQueryMapper;
use App\Support\Enum\AppContactStatus;
use NeoPHP\Component\Http\Request\Request;

class AdminContactRequestMapper
{
    public static function filterStatus(Request $request): ?AppContactStatus
    {
        if (!$request->query->has('status')) {
            return AppContactStatus::NEW;
        }

        return AppContactStatus::tryFrom($request->query->getString('status'));
    }

    public static function closeAfterReply(Request $request): bool
    {
        return $request->request->getBoolean('close');
    }

    public static function returnQuery(Request $request): array
    {
        return AdminQueryMapper::returnQuery($request, AppContactStatus::NEW->value);
    }
}