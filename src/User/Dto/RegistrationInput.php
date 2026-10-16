<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class RegistrationInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public string $firstName = '';

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\Regex(pattern: ValidationPattern::PHONE)]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: ValidationPattern::PASSWORD, message: ValidationPattern::PASSWORD_MESSAGE)]
    public string $password = '';

    #[Assert\IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.')]
    public bool $acceptTerms = false;
}