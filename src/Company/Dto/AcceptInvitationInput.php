<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class AcceptInvitationInput
{
    #[Assert\Length(max: 80)]
    public ?string $firstName = null;

    #[Assert\Length(max: 80)]
    public ?string $lastName = null;

    #[Assert\Regex(pattern: '/^(?=.*[A-Za-z])(?=.*\\d).{12,}$/', message: 'Le mot de passe doit faire au moins 12 caractères, avec des lettres et des chiffres.')]
    public ?string $password = null;
}