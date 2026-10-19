<?php

namespace App\User\Dto;

use App\Shared\Helper\ValidationPattern;
use NeoPHP\Component\Validator\Constraint as Assert;

class AlertInput
{
    #[Assert\Length(min: 2, max: 120)]
    public ?string $label = null;

    public ?string $citySlug = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Range(min: 1, max: 30)]
    public ?int $radiusKm = null;

    #[Assert\Regex(pattern: ValidationPattern::TIME)]
    public ?string $startTime = null;

    #[Assert\Regex(pattern: ValidationPattern::TIME)]
    public ?string $endTime = null;

    #[Assert\PositiveOrZero]
    public ?int $maxPrice = null;

    public ?array $categorySlugs = null;

    public ?bool $enabled = null;
}