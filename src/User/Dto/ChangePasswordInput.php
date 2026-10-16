<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class ChangePasswordInput
{
    #[Assert\NotBlank]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: ValidationPattern::PASSWORD, message: ValidationPattern::PASSWORD_MESSAGE)]
    public string $newPassword = '';
}