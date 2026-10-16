<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SanctionInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 500)]
    public string $reason = '';

    #[Assert\Range(min: 1, max: 365)]
    public ?int $days = null;
}