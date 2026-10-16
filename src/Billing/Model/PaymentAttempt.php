<?php

namespace App\Billing\Model;

use App\Billing\Enum\PaymentStatus;
use App\Entity\Company\CompanyPayment;

class PaymentAttempt
{
    public function __construct(
        private CompanyPayment $payment,
        private ?string $clientSecret = null,
    ) {
    }

    public function getPayment(): CompanyPayment
    {
        return $this->payment;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function requiresAction(): bool
    {
        return $this->payment->getStatus() === PaymentStatus::REQUIRES_ACTION && $this->clientSecret !== null;
    }
}