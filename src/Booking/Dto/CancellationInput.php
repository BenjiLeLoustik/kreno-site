<?php

namespace App\Booking\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class CancellationInput
{
    #[Assert\Length(max: 255)]
    public ?string $reason = null;
}