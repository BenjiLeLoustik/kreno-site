<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::PAID => Translation::translate('Payé'),
            self::PENDING => Translation::translate('En attente'),
            self::FAILED => Translation::translate('Refusé'),
            self::RETRY => Translation::translate('Nouvelle tentative'),
            self::NONE => Translation::translate('Aucune facture'),
        };
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED || $this === self::RETRY;
    }
}