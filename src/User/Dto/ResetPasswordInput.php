<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class ResetPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: ValidationPattern::PASSWORD, message: ValidationPattern::PASSWORD_MESSAGE)]
    public string $password = '';

    #[Assert\NotBlank]
    public string $passwordConfirmation = '';
}