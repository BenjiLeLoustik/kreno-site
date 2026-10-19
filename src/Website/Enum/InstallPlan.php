<?php

namespace App\Website\Enum;

enum InstallPlan
{
    case PER_BOOKING;
    case SUBSCRIPTION;

    public function defaults(): array
    {
        return match ($this) {
            self::PER_BOOKING => [
                'billing' => PlanBilling::PER_BOOKING->value,
                'active' => true,
                'code' => 'reservation',
                'name' => 'À la réservation',
                'description' => 'Vous ne payez que les créneaux réservés.',
                'price' => '2,90',
                'yearlyPrice' => null,
                'trialDays' => 0,
                'maxEstablishments' => null,
                'commissionFree' => false,
                'featured' => false,
                'advancedStats' => false,
            ],
            self::SUBSCRIPTION => [
                'billing' => PlanBilling::SUBSCRIPTION->value,
                'active' => true,
                'code' => 'abonnement',
                'name' => 'Abonnement',
                'description' => 'Aucune commission, mise en avant et statistiques détaillées.',
                'price' => '39,00',
                'yearlyPrice' => '390,00',
                'trialDays' => 14,
                'maxEstablishments' => 3,
                'commissionFree' => true,
                'featured' => true,
                'advancedStats' => true,
            ],
        };
    }
}