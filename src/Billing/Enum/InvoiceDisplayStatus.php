<?php

namespace App\Billing\Enum;

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
            self::OPEN => 'À payer',
            self::PAID => 'Payée',
            self::FAILED => 'Échec du paiement',
            self::REFUNDED => 'Avoir',
            self::VOID => 'Annulée',
        };
    }

    public function stamp(): string
    {
        return match ($this) {
            self::OPEN, self::FAILED => 'À PAYER',
            self::PAID => 'PAYÉE',
            self::REFUNDED => 'AVOIR',
            self::VOID => 'ANNULÉE',
        };
    }

    public function isPayable(): bool
    {
        return $this === self::OPEN || $this === self::FAILED;
    }
}