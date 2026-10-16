<?php

namespace App\Website\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class WebsitePromoCodeInput
{
    #[Assert\Regex(pattern: '/^[A-Za-z0-9_-]{3,40}$/')]
    public ?string $code = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    public ?string $planCode = null;

    #[Assert\Range(min: 0, max: 100)]
    public ?int $discountPercent = null;

    #[Assert\Range(min: 0, max: 24)]
    public ?int $freeMonths = null;

    #[Assert\Range(min: 1, max: 36)]
    public ?int $durationMonths = null;

    #[Assert\Positive]
    public ?int $maxUses = null;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?bool $active = null;
}