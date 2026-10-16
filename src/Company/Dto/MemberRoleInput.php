<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class MemberRoleInput
{
    #[Assert\Choice(choices: ['admin', 'manager', 'planner', 'receptionist', 'employee'])]
    public string $role = 'employee';

    public ?int $establishmentId = null;

    public bool $canManageBookings = false;
}