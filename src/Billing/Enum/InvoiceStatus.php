<?php

declare(strict_types=1);

namespace App\Billing\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PAID = 'paid';
    case FAILED = 'failed';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::OPEN => 'À payer',
            self::PAID => 'Payée',
            self::FAILED => 'Échec',
            self::VOID => 'Annulée',
        };
    }
}