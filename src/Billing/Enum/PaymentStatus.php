<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case REQUIRES_ACTION = 'requires_action';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => Translation::translate('En cours'),
            self::REQUIRES_ACTION => Translation::translate('Validation bancaire requise'),
            self::SUCCEEDED => Translation::translate('Réussi'),
            self::FAILED => Translation::translate('Refusé'),
            self::CANCELED => Translation::translate('Annulé'),
        };
    }
}