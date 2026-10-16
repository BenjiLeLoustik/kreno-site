<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class GraceInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 60)]
    public ?int $days = null;

    #[Assert\NotBlank(message: 'Indiquez le motif.')]
    #[Assert\Length(min: 5, max: 500)]
    public ?string $reason = null;
}