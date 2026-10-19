<?php

namespace App\User\Dto;

use App\User\Enum\UserRole;
use NeoPHP\Component\Validator\Constraint as Assert;

class StaffRoleInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [UserRole::class, 'staffValues'])]
    public ?string $role = null;
}