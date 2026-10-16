<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class ImageInput
{
    #[Assert\Length(max: 160)]
    public ?string $alt = null;
}