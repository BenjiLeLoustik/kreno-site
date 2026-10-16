<?php

declare(strict_types=1);

namespace App\Report\Enum;

enum ReportReason: string
{
    case PRICE_MISMATCH = 'price_mismatch';
    case CLOSED = 'closed';
    case SERVICE_MISMATCH = 'service_mismatch';
    case LAST_MINUTE_CANCELLATION = 'last_minute_cancellation';
    case BEHAVIOR = 'behavior';
    case NO_SHOW = 'no_show';
    case LATE = 'late';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PRICE_MISMATCH => 'Prix différent',
            self::CLOSED => 'Établissement fermé',
            self::SERVICE_MISMATCH => 'Prestation non conforme',
            self::LAST_MINUTE_CANCELLATION => 'Annulation de dernière minute',
            self::BEHAVIOR => 'Comportement inapproprié',
            self::NO_SHOW => 'Absence non prévenue',
            self::LATE => 'Retard important',
            self::OTHER => 'Autre',
        };
    }
}