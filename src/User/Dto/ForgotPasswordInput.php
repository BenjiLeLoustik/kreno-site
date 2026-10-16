<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ForgotPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';
}