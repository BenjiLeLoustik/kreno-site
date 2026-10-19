<?php

namespace App\Catalog\Dto;

use App\Establishment\Helper\ServiceSuggestionHelper;
use NeoPHP\Component\Validator\Constraint as Assert;

class ServiceSuggestionInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    #[Assert\NotNull]
    #[Assert\Choice(choices: ServiceSuggestionHelper::DURATIONS)]
    public ?int $duration = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?float $price = null;
}