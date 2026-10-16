<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PayInvoiceInput
{
    #[Assert\Positive]
    public ?int $paymentMethodId = null;

    public bool $makeDefault = false;
}