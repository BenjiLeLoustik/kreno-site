<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PromoCheckInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 40)]
    public ?string $code = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
    public ?string $plan = null;

    #[Assert\Choice(choices: ['monthly', 'yearly'])]
    public string $period = 'monthly';
}