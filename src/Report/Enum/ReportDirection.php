<?php

declare(strict_types=1);

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportDirection: string
{
    case FROM_CUSTOMER = 'from_customer';
    case FROM_ESTABLISHMENT = 'from_establishment';

    public function label(): string
    {
        return match ($this) {
            self::FROM_CUSTOMER => Translation::translate('Signalé par un client'),
            self::FROM_ESTABLISHMENT => Translation::translate('Signalé par un établissement'),
        };
    }
}