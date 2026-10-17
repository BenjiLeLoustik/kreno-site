<?php

namespace App\Billing\Mapper;

use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\SubscriptionCancelInput;
use App\Billing\Enum\BillingPeriod;
use NeoPHP\Component\Http\Request\Request;

class SubscriptionRequestMapper
{
    public static function period(Request $request): ?BillingPeriod
    {
        return BillingPeriod::tryFrom($request->query->getString('period'));
    }

    public static function plan(Request $request): string
    {
        return $request->query->getString('plan');
    }

    public static function promoCode(Request $request): ?string
    {
        return trim($request->query->getString('promoCode')) ?: null;
    }

    public static function change(PlanChoiceInput $input): PlanChoiceInput
    {
        $input->promoCode = self::filled($input->promoCode);

        return $input;
    }

    public static function signupPlan(PlanChoiceInput $input): PlanChoiceInput
    {
        $input->paymentMethodId = null;

        return $input;
    }

    public static function quoteQuery(PlanChoiceInput $input): array
    {
        return ['plan' => $input->plan, 'period' => $input->period, 'promoCode' => $input->promoCode];
    }

    public static function cancel(Request $request): SubscriptionCancelInput
    {
        $input = new SubscriptionCancelInput();
        $input->reason = $request->request->getString('reason') ?: null;
        $input->comment = trim($request->request->getString('comment')) ?: null;
        $input->acknowledged = $request->request->getBoolean('acknowledged');

        return $input;
    }

    private static function filled(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}