<?php

namespace App\Billing\Gateway;

use App\Billing\Enum\PaymentMethodType;
use App\Billing\Model\GatewayPaymentMethod;
use App\Billing\Model\GatewayPaymentResult;
use App\Billing\Model\GatewaySetup;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyPaymentMethod;

interface PaymentGatewayInterface
{
    public function createSetup(Company $company, PaymentMethodType $type, ?CompanyPaymentMethod $existing = null): GatewaySetup;

    public function completeSetup(string $setupReference): GatewayPaymentMethod;

    public function charge(CompanyPaymentMethod $method, int $amount, string $description, string $idempotencyKey, bool $offSession): GatewayPaymentResult;

    public function retrieveCharge(string $reference): GatewayPaymentResult;

    public function detach(CompanyPaymentMethod $method): void;

    public function parseWebhook(string $payload, string $signature): ?GatewayPaymentResult;

    public function getPublicKey(): string;
}