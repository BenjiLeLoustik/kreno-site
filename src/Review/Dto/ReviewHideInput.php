<?php

namespace App\Review\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ReviewHideInput
{
    #[Assert\NotBlank(message: 'Indiquez le motif du masquage.')]
    #[Assert\Length(max: 500)]
    public ?string $reason = null;
}