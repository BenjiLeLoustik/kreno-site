<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ClosureInput
{
    #[Assert\NotBlank]
    #[Assert\Date]
    public ?string $startsOn = null;

    #[Assert\NotBlank]
    #[Assert\Date]
    public ?string $endsOn = null;

    #[Assert\Length(max: 160)]
    public ?string $reason = null;
}