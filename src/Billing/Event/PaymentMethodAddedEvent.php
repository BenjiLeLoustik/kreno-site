<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanyPaymentMethod;

class PaymentMethodAddedEvent
{
    public function __construct(
        private CompanyPaymentMethod $paymentMethod,
    ) {
    }

    public function getPaymentMethod(): CompanyPaymentMethod
    {
        return $this->paymentMethod;
    }
}