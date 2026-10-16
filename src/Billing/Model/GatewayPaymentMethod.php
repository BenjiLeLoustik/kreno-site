<?php

namespace App\Billing\Model;

use App\Billing\Enum\PaymentMethodType;

class GatewayPaymentMethod
{
    public function __construct(
        private string $reference,
        private PaymentMethodType $type,
        private ?string $brand = null,
        private ?string $last4 = null,
        private ?int $expMonth = null,
        private ?int $expYear = null,
        private ?string $holderName = null,
        private ?string $mandateReference = null,
    ) {
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getType(): PaymentMethodType
    {
        return $this->type;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function getExpMonth(): ?int
    {
        return $this->expMonth;
    }

    public function getExpYear(): ?int
    {
        return $this->expYear;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function getMandateReference(): ?string
    {
        return $this->mandateReference;
    }
}