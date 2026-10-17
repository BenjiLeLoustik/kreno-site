<?php

namespace App\Billing\Enum;

enum SubscriptionFlash
{
    case CHANGE_SCHEDULED;
    case CHANGED;
    case CANCELLED;
    case CANCELLATION_REVOKED;
    case NEXT_DUE_DATE;
    case PERIOD_END;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CHANGE_SCHEDULED => 'Changement enregistré : il prendra effet le %s.',
            self::CHANGED => 'Vous êtes maintenant sur la formule %s.',
            self::CANCELLED => 'Résiliation enregistrée. Votre formule reste active jusqu\'au %s.',
            self::CANCELLATION_REVOKED => 'Résiliation annulée : votre abonnement continue.',
            self::NEXT_DUE_DATE => 'à la prochaine échéance',
            self::PERIOD_END => 'terme de la période',
        };
    }
}