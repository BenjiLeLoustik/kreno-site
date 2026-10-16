<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Serializer\Attribute\Type;
use NeoPHP\Component\Validator\Constraint as Assert;

class OpeningHoursInput
{
    #[Type('App\Establishment\Dto\OpeningHourInput[]')]
    #[Assert\Count(max: 21)]
    #[Assert\Valid]
    public array $hours = [];
}