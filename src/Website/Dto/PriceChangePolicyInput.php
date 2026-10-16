<?php

namespace App\Website\Dto;

use App\Website\Enum\PriceChangePolicy;
use NeoPHP\Component\Validator\Constraint as Assert;

class PriceChangePolicyInput
{
    public ?PriceChangePolicy $policy = null;

    #[Assert\Range(min: 0, max: 90)]
    public ?int $noticeDays = null;

    #[Assert\Length(min: 2, max: 120)]
    public ?string $pricingName = null;
}