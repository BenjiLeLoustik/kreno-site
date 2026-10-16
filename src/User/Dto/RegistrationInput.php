<?php

namespace App\User\Dto;

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

    #[Assert\Regex(pattern: '/^\+?[0-9 .-]{10,20}$/')]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(?=.*[A-Za-z])(?=.*\\d).{12,}$/', message: 'Le mot de passe doit faire au moins 12 caractères, avec des lettres et des chiffres.')]
    public string $password = '';

    #[Assert\IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.')]
    public bool $acceptTerms = false;
}