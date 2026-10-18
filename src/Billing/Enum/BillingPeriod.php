<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum BillingPeriod: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => Translation::translate('Mensuel'),
            self::YEARLY => Translation::translate('Annuel'),
        };
    }
}