<?php

namespace App\User\Mapper;

use App\User\Enum\UserStatus;
use NeoPHP\Component\Http\Request\Request;

class AdminUserRequestMapper
{
    public static function status(Request $request): ?UserStatus
    {
        return UserStatus::tryFrom($request->query->getString('status'));
    }
}