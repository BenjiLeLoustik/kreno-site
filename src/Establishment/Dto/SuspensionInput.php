<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SuspensionInput
{
    #[Assert\NotBlank(message: 'Indiquez le motif de la suspension.')]
    #[Assert\Length(min: 5, max: 500)]
    public ?string $reason = null;
}