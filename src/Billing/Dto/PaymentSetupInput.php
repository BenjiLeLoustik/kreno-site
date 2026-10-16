<?php

namespace App\Billing\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class PaymentSetupInput
{
    #[Assert\Choice(choices: ['card', 'sepa_debit'])]
    public string $type = 'card';
}