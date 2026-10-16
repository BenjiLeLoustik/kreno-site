<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class ProfileInput
{
    #[Assert\Length(min: 1, max: 80)]
    public ?string $firstName = null;

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\Regex(pattern: ValidationPattern::PHONE)]
    public ?string $phone = null;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;
}