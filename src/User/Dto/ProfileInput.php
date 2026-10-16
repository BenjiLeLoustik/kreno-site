<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ProfileInput
{
    #[Assert\Length(min: 1, max: 80)]
    public ?string $firstName = null;

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\Regex(pattern: '/^\+?[0-9 .-]{10,20}$/')]
    public ?string $phone = null;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;
}