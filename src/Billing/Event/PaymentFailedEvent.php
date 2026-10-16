<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanyPayment;

class PaymentFailedEvent
{
    public function __construct(
        private CompanyPayment $payment,
        private bool $final = false,
    ) {
    }

    public function getPayment(): CompanyPayment
    {
        return $this->payment;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }
}