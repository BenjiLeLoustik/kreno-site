<?php

declare(strict_types=1);

namespace App\Billing\Enum;

enum InvoiceType: string
{
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => 'Facture',
            self::CREDIT_NOTE => 'Avoir',
        };
    }
}