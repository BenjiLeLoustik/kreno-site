<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PlanChoiceInput
{
    #[Assert\NotBlank(message: 'Choisissez une formule.')]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
    public ?string $plan = null;

    #[Assert\Choice(choices: ['monthly', 'yearly'])]
    public string $period = 'monthly';

    #[Assert\Length(max: 40)]
    public ?string $promoCode = null;

    #[Assert\Positive]
    public ?int $paymentMethodId = null;
}