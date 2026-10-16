<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SubscriptionCancelInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['price', 'volume', 'pause', 'close', 'other'])]
    public ?string $reason = null;

    #[Assert\Length(max: 1000)]
    public ?string $comment = null;

    #[Assert\IsTrue(message: 'Confirmez avoir pris connaissance du changement de facturation.')]
    public bool $acknowledged = false;
}