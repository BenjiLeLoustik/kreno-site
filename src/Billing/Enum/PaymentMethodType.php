<?php

declare(strict_types=1);

namespace App\Billing\Enum;

enum PaymentMethodType: string
{
    case CARD = 'card';
    case SEPA_DEBIT = 'sepa_debit';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Carte bancaire',
            self::SEPA_DEBIT => 'Prélèvement SEPA',
        };
    }
}