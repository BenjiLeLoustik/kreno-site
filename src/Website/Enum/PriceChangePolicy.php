<?php

declare(strict_types=1);

namespace App\Website\Enum;

enum PriceChangePolicy: string
{
    case KEEP_UNTIL_RENEWAL = 'keep_until_renewal';
    case KEEP_TWELVE_MONTHS = 'keep_twelve_months';
    case APPLY_IMMEDIATELY = 'apply_immediately';

    public function label(): string
    {
        return match ($this) {
            self::KEEP_UNTIL_RENEWAL => 'Prix conservé jusqu\'à la prochaine échéance',
            self::KEEP_TWELVE_MONTHS => 'Prix conservé pendant 12 mois',
            self::APPLY_IMMEDIATELY => 'Nouveau prix immédiat',
        };
    }
}