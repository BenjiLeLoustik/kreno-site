<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum BillingGesture: string
{
    case NONE = 'none';
    case FREE_MONTH = 'free_month';
    case DISCOUNT = 'discount';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::NONE => Translation::translate('Aucun'),
            self::FREE_MONTH => Translation::translate('1 mois offert'),
            self::DISCOUNT => Translation::translate('-20 % pendant 3 mois'),
            self::CREDIT => Translation::translate('Avoir sur la dernière facture'),
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