<?php

namespace App\Catalog\Dto;

use App\Catalog\Enum\CityStatus;
use NeoPHP\Component\Validator\Constraint as Assert;

class CityInput
{
    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;

    #[Assert\Regex(pattern: '/^(\d{2,3}|2A|2B)$/')]
    public ?string $departmentCode = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Range(min: 1, max: 100)]
    public ?int $radiusKm = null;

    public ?CityStatus $status = null;

    #[Assert\PositiveOrZero]
    public ?int $position = null;
}