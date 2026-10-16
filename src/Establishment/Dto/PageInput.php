<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Serializer\Attribute\Type;
use NeoPHP\Component\Validator\Constraint as Assert;

class PageInput
{
    #[Assert\Length(max: 400)]
    public ?string $description = null;

    #[Type('App\Establishment\Dto\OpeningHourInput[]')]
    #[Assert\Count(min: 1, max: 21, minMessage: 'Indiquez au moins un jour d\'ouverture.')]
    #[Assert\Valid]
    public array $hours = [];
}