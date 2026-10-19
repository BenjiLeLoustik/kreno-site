<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::DRAFT => Translation::translate('Brouillon'),
            self::OPEN => Translation::translate('À payer'),
            self::PAID => Translation::translate('Payée'),
            self::FAILED => Translation::translate('Échec'),
            self::VOID => Translation::translate('Annulée'),
        };
    }
}