<?php

namespace App\Company\Dto;

use App\Company\Enum\CompanyKind;
use NeoPHP\Component\Validator\Constraint as Assert;

class ProAccountInput
{
    public CompanyKind $kind = CompanyKind::SOLO;

    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public string $lastName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[0-9 .-]{10,20}$/')]
    public string $phone = '';

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Za-z])(?=.*\\d).{12,}$/',
        message: 'Le mot de passe doit faire au moins 12 caractères, avec des lettres et des chiffres.'
    )]
    public string $password = '';
}