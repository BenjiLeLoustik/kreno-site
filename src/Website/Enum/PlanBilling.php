<?php

declare(strict_types=1);

namespace App\Website\Enum;

enum PlanBilling: string
{
    case PER_BOOKING = 'per_booking';
    case SUBSCRIPTION = 'subscription';

    public function label(): string
    {
        return match ($this) {
            self::PER_BOOKING => 'À la réservation',
            self::SUBSCRIPTION => 'Abonnement',
        };
    }
}