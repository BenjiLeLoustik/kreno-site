<?php

declare(strict_types=1);

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum PriceChangePolicy: string
{
    case KEEP_UNTIL_RENEWAL = 'keep_until_renewal';
    case KEEP_TWELVE_MONTHS = 'keep_twelve_months';
    case APPLY_IMMEDIATELY = 'apply_immediately';

    public function label(): string
    {
        return match ($this) {
            self::KEEP_UNTIL_RENEWAL => Translation::translate('Prix conservé jusqu\'à la prochaine échéance'),
            self::KEEP_TWELVE_MONTHS => Translation::translate('Prix conservé pendant 12 mois'),
            self::APPLY_IMMEDIATELY => Translation::translate('Nouveau prix immédiat'),
        };
    }
}