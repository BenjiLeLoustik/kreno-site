<?php

namespace App\Billing\Provider;

use App\Billing\Enum\PaymentMethodType;
use App\Billing\Exception\PaymentGatewayException;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Service\PaymentMethodService;
use App\Entity\Company\Company;
use App\Shared\Exception\BusinessRuleException;

class PaymentSetupProvider
{
    public function __construct(
        private PaymentMethodService $methodService,
        private PaymentGatewayInterface $gateway,
    ) {
    }

    public function start(Company $company, PaymentMethodType $type): array
    {
        try {
            $setup = $this->methodService->startSetup($company, $type);
        } catch (BusinessRuleException|PaymentGatewayException $exception) {
            return [
                'error' => $exception->getMessage()
            ];
        }

        return [
            'setup' => $setup->toArray(),
            'gatewayKey' => $this->gateway->getPublicKey()
        ];
    }
}