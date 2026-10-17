<?php

namespace App\Billing\Mapper;

use App\Billing\Dto\BillingInfoInput;
use App\Billing\Dto\PaymentMethodInput;
use App\Billing\Dto\PaymentSetupInput;
use App\Billing\Enum\PaymentMethodType;
use NeoPHP\Component\Http\Request\Request;

class BillingRequestMapper
{
    public const BILLING_FIELDS = [
        'billingName',
        'billingEmail',
        'vatNumber',
        'addressLine1',
        'addressLine2',
        'postalCode',
        'locality',
    ];

    public static function info(BillingInfoInput $input): BillingInfoInput
    {
        $input->vatNumber = self::vatNumber(self::filled($input->vatNumber));
        $input->addressLine2 = self::filled($input->addressLine2, false);

        return $input;
    }

    public static function infoFromValues(array $values): BillingInfoInput
    {
        $input = new BillingInfoInput();

        foreach (self::BILLING_FIELDS as $field) {
            $input->{$field} = self::filled((string) ($values[$field] ?? ''));
        }

        $input->vatNumber = self::vatNumber($input->vatNumber);

        return $input;
    }

    public static function paymentMethod(Request $request): PaymentMethodInput
    {
        $input = new PaymentMethodInput();
        $input->setupReference = trim($request->request->getString('setupReference')) ?: null;
        $input->makeDefault = $request->request->getBoolean('makeDefault');
        $input->mandateAccepted = $request->request->getBoolean('mandateAccepted');

        return $input;
    }

    public static function setupType(PaymentSetupInput $input): PaymentMethodType
    {
        return PaymentMethodType::tryFrom($input->type) ?? PaymentMethodType::CARD;
    }

    public static function methodId(Request $request): int
    {
        return $request->request->getInt('paymentMethodId');
    }

    public static function makeDefault(Request $request): bool
    {
        return $request->request->getBoolean('makeDefault');
    }

    private static function filled(?string $value, bool $trim = true): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (!$trim) {
            return $value;
        }

        return trim($value);
    }

    private static function vatNumber(?string $vatNumber): ?string
    {
        if ($vatNumber === null) {
            return null;
        }

        return strtoupper(str_replace(' ', '', $vatNumber));
    }
}