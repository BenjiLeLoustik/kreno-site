<?php

namespace App\Billing\Model;

class GatewaySetup
{
    public function __construct(
        private string $reference,
        private string $clientSecret,
    ) {
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'clientSecret' => $this->clientSecret
        ];
    }
}