<?php

declare(strict_types=1);

namespace App\Billing\Enum;

enum BillingPeriod: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensuel',
            self::YEARLY => 'Annuel',
        };
    }
}