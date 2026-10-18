<?php

declare(strict_types=1);

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::PENDING => Translation::translate('En attente de mise en ligne'),
            self::ACTIVE => Translation::translate('Active'),
            self::PAST_DUE => Translation::translate('Paiement en retard'),
            self::PAUSED => Translation::translate('En pause'),
            self::CANCELED => Translation::translate('Résiliée'),
        };
    }
}