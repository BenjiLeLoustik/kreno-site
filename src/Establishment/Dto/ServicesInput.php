<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Serializer\Attribute\Type;
use NeoPHP\Component\Validator\Constraint as Assert;

class ServicesInput
{
    #[Type('App\Establishment\Dto\ServiceInput[]')]
    #[Assert\Count(min: 1, max: 30, minMessage: 'Ajoutez au moins une prestation.')]
    #[Assert\Valid]
    public array $services = [];

    #[Assert\Range(min: 0, max: 90)]
    public ?int $defaultDiscount = null;
}