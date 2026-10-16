<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class RetentionOfferInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['price', 'volume', 'pause'])]
    public ?string $offer = null;
}