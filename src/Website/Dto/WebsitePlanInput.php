<?php

namespace App\Website\Dto;

use App\Website\Enum\PlanBilling;
use NeoPHP\Component\Validator\Constraint as Assert;

class WebsitePlanInput
{
    #[Assert\Regex(pattern: '/^[a-z0-9-]{2,40}$/')]
    public ?string $code = null;

    #[Assert\Length(min: 2, max: 80)]
    public ?string $name = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    public ?PlanBilling $billing = null;

    #[Assert\PositiveOrZero]
    public ?int $price = null;

    #[Assert\PositiveOrZero]
    public ?int $yearlyPrice = null;

    #[Assert\Range(min: 0, max: 100)]
    public ?int $vatRate = null;

    #[Assert\Range(min: 0, max: 90)]
    public ?int $trialDays = null;

    #[Assert\Range(min: 0, max: 36)]
    public ?int $commitmentMonths = null;

    #[Assert\Range(min: 1, max: 1000)]
    public ?int $maxEstablishments = null;

    public ?bool $unlimitedEstablishments = null;

    public ?bool $commissionFree = null;

    public ?bool $featured = null;

    public ?bool $advancedStats = null;

    public ?bool $active = null;

    #[Assert\PositiveOrZero]
    public ?int $position = null;
}