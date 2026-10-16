<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PaymentMethodInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $setupReference = null;

    public bool $makeDefault = false;

    public bool $mandateAccepted = false;
}