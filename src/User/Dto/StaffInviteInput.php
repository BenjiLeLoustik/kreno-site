<?php

namespace App\User\Dto;

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
    #[Assert\Choice(choices: ['ROLE_SUPPORT', 'ROLE_MODERATOR', 'ROLE_ADMIN'])]
    public ?string $role = null;
}