<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class StaffRoleInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ROLE_SUPPORT', 'ROLE_MODERATOR', 'ROLE_ADMIN'])]
    public ?string $role = null;
}