<?php

namespace App\Company\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class VerificationCodeInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Le code contient 6 chiffres.')]
    public string $code = '';
}