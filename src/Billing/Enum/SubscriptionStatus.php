<?php

declare(strict_types=1);

namespace App\Billing\Enum;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case PAUSED = 'paused';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente de mise en ligne',
            self::ACTIVE => 'Active',
            self::PAST_DUE => 'Paiement en retard',
            self::PAUSED => 'En pause',
            self::CANCELED => 'Résiliée',
        };
    }
}