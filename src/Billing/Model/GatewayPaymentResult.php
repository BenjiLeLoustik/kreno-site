<?php

namespace App\Billing\Model;

use App\Billing\Enum\PaymentStatus;

class GatewayPaymentResult
{
    public function __construct(
        private ?string $reference,
        private PaymentStatus $status,
        private ?string $clientSecret = null,
        private ?string $failureCode = null,
        private ?string $failureMessage = null,
    ) {
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }
}