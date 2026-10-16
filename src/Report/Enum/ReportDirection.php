<?php

declare(strict_types=1);

namespace App\Report\Enum;

enum ReportDirection: string
{
    case FROM_CUSTOMER = 'from_customer';
    case FROM_ESTABLISHMENT = 'from_establishment';

    public function label(): string
    {
        return match ($this) {
            self::FROM_CUSTOMER => 'Signalé par un client',
            self::FROM_ESTABLISHMENT => 'Signalé par un établissement',
        };
    }
}