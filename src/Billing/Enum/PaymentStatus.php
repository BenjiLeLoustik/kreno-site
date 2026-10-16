<?php

declare(strict_types=1);

namespace App\Billing\Enum;

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
            self::PENDING => 'En cours',
            self::REQUIRES_ACTION => 'Validation bancaire requise',
            self::SUCCEEDED => 'Réussi',
            self::FAILED => 'Refusé',
            self::CANCELED => 'Annulé',
        };
    }
}