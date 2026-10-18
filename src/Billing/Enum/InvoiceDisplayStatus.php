<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum InvoiceDisplayStatus: string
{
    case OPEN = 'open';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => Translation::translate('À payer'),
            self::PAID => Translation::translate('Payée'),
            self::FAILED => Translation::translate('Échec du paiement'),
            self::REFUNDED => Translation::translate('Avoir'),
            self::VOID => Translation::translate('Annulée'),
        };
    }

    public function stamp(): string
    {
        return match ($this) {
            self::OPEN, self::FAILED => Translation::translate('À PAYER'),
            self::PAID => Translation::translate('PAYÉE'),
            self::REFUNDED => Translation::translate('AVOIR'),
            self::VOID => Translation::translate('ANNULÉE'),
        };
    }

    public function isPayable(): bool
    {
        return $this === self::OPEN || $this === self::FAILED;
    }
}