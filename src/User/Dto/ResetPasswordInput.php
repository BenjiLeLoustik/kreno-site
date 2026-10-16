<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ResetPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(?=.*[A-Za-z])(?=.*\\d).{12,}$/', message: 'Le mot de passe doit faire au moins 12 caractères, avec des lettres et des chiffres.')]
    public string $password = '';

    #[Assert\NotBlank]
    public string $passwordConfirmation = '';
}