<?php

namespace App\User\Mapper;

use App\User\Dto\StaffRoleInput;
use App\User\Enum\UserRole;

class StaffRequestMapper
{
    public static function role(StaffRoleInput $input): UserRole
    {
        return UserRole::from((string) $input->role);
    }
}