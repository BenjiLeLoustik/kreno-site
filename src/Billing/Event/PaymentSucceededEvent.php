<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanyPayment;

class PaymentSucceededEvent
{
    public function __construct(
        private CompanyPayment $payment,
    ) {
    }

    public function getPayment(): CompanyPayment
    {
        return $this->payment;
    }
}