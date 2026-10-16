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

        return sprintf('%s •••• %s', $method->getBrand() ?? 'Carte', $method->getLast4());
    }

    public static function brand(CompanyPaymentMethod $method): string
    {
        return $method->getType() === PaymentMethodType::SEPA_DEBIT ? 'SEPA' : mb_strtoupper($method->getBrand() ?? 'CB');
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
        return $method->getExpMonth() !== null && $method->getExpYear() !== null ? sprintf('%02d/%02d', $method->getExpMonth(), $method->getExpYear() % 100) : null;
    }
}