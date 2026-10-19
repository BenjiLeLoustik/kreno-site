<?php

declare(strict_types=1);

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum PlanBilling: string
{
    case PER_BOOKING = 'per_booking';
    case SUBSCRIPTION = 'subscription';

    public function label(): string
    {
        return match ($this) {
            self::PER_BOOKING => Translation::translate('À la réservation'),
            self::SUBSCRIPTION => Translation::translate('Abonnement'),
        };
    }

    public function unitLabel(): string
    {
        return match ($this) {
            self::PER_BOOKING => Translation::translate('HT / créneau réservé'),
            self::SUBSCRIPTION => Translation::translate('HT / mois'),
        };
    }
}