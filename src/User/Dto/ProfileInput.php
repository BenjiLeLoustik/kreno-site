<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class ProfileInput
{
    #[Assert\NotBlank(message: 'Indiquez votre prénom.')]
    #[Assert\Length(max: 80)]
    public ?string $firstName = null;

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\Regex(pattern: ValidationPattern::PHONE)]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;
}