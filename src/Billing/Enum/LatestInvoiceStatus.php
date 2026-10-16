<?php

namespace App\Billing\Enum;

enum LatestInvoiceStatus: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case RETRY = 'retry';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::PAID => 'Payé',
            self::PENDING => 'En attente',
            self::FAILED => 'Refusé',
            self::RETRY => 'Nouvelle tentative',
            self::NONE => 'Aucune facture',
        };
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED || $this === self::RETRY;
    }
}