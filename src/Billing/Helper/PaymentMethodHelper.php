<?php

namespace App\Billing\Helper;

use App\Billing\Enum\PaymentMethodType;
use App\Entity\Company\CompanyPaymentMethod;
use DateTimeImmutable;

class PaymentMethodHelper
{
    public static function label(?CompanyPaymentMethod $method): ?string
    {
        if ($method === null) {
            return null;
        }

        if ($method->getType() === PaymentMethodType::SEPA_DEBIT) {
            return sprintf('Prélèvement SEPA •••• %s', $method->getLast4());
        }

        $paymentMethod = $method->getBrand() ?? 'carte';

        return sprintf('%s •••• %s', $paymentMethod, $method->getLast4());
    }

    public static function brand(CompanyPaymentMethod $method): string
    {
        if ($method->getType() === PaymentMethodType::SEPA_DEBIT) {
            return 'SEPA';
        }

        $paymentMethod = $method->getBrand() ?? 'CB';
        return mb_strtoupper($paymentMethod);
    }

    public static function isExpired(CompanyPaymentMethod $method, ?DateTimeImmutable $now = null): bool
    {
        if ($method->getExpMonth() === null || $method->getExpYear() === null) {
            return false;
        }

        $now ??= new DateTimeImmutable();

        return (int) $now->format('Y') * 12 + (int) $now->format('n') > $method->getExpYear() * 12 + $method->getExpMonth();
    }

    public static function expiry(CompanyPaymentMethod $method): ?string
    {
        if ($method->getExpMonth() !== null && $method->getExpYear() !== null) {
            return sprintf('%02d/%02d', $method->getExpMonth(), $method->getExpYear());
        }

        return null;
    }
}