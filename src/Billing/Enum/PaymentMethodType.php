<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum PaymentMethodType: string
{
    case CARD = 'card';
    case SEPA_DEBIT = 'sepa_debit';

    public function label(): string
    {
        return match ($this) {
            self::CARD => Translation::translate('Carte bancaire'),
            self::SEPA_DEBIT => Translation::translate('Prélèvement SEPA'),
        };
    }
}