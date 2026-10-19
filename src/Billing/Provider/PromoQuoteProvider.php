<?php

namespace App\Billing\Provider;

use App\Billing\Dto\PromoCheckInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Exception\PromoNotApplicableException;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Shared\Exception\BusinessRuleException;

class PromoQuoteProvider
{
    public function __construct(
        private PlanCatalog $planCatalog,
        private PromoCodeService $promos,
        private PriceCalculator $prices,
    ) {
    }

    public function quote(PromoCheckInput $input): array
    {
        try {
            return $this->resolve($input);
        } catch (BusinessRuleException $exception) {
            return [
                'error' => $exception->getMessage()
            ];
        }
    }

    private function resolve(PromoCheckInput $input): array
    {
        $plan = $this->planCatalog->get((string) $input->plan);
        $promo = $this->promos->resolve($input->code, $plan);

        if ($promo === null) {
            throw new PromoNotApplicableException();
        }

        return [
            'promo' => [
                'code' => $promo->getCode(),
                'label' => $this->promos->describe($promo)
            ],
            'quote' => $this->prices->quote($plan, BillingPeriod::from($input->period), $promo),
        ];
    }
}