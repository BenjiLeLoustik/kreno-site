<?php

namespace App\User\Dto;

use App\User\Enum\UserRole;
use NeoPHP\Component\Validator\Constraint as Assert;

class StaffInviteInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public ?string $firstName = null;

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [UserRole::class, 'staffValues'])]
    public ?string $role = null;
}