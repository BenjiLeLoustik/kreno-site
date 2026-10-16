<?php

namespace App\Billing\Enum;

enum BillingGesture: string
{
    case NONE = 'none';
    case FREE_MONTH = 'free_month';
    case DISCOUNT = 'discount';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Aucun',
            self::FREE_MONTH => '1 mois offert',
            self::DISCOUNT => '-20 % pendant 3 mois',
            self::CREDIT => 'Avoir sur la dernière facture',
        };
    }

    public function promoCode(): ?string
    {
        return match ($this) {
            self::FREE_MONTH => 'GESTE-MOIS',
            self::DISCOUNT => 'GESTE-20',
            default => null,
        };
    }
}