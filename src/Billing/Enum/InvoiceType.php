<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum InvoiceType: string
{
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => Translation::translate('Facture'),
            self::CREDIT_NOTE => Translation::translate('Avoir'),
        };
    }
}