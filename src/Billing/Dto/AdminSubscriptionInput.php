<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class AdminSubscriptionInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
    public ?string $plan = null;

    #[Assert\Choice(choices: ['monthly', 'yearly'])]
    public string $period = 'monthly';

    #[Assert\Choice(choices: ['immediate', 'renewal'])]
    public string $application = 'immediate';

    #[Assert\Choice(choices: ['none', 'free_month', 'discount', 'credit'])]
    public string $gesture = 'none';

    #[Assert\NotBlank(message: 'Indiquez le motif.')]
    #[Assert\Length(min: 5, max: 500)]
    public ?string $reason = null;
}