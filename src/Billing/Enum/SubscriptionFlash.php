<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::CHANGE_SCHEDULED => Translation::translate('Changement enregistré : il prendra effet le %s.'),
            self::CHANGED => Translation::translate('Vous êtes maintenant sur la formule %s.'),
            self::CANCELLED => Translation::translate('Résiliation enregistrée. Votre formule reste active jusqu\'au %s.'),
            self::CANCELLATION_REVOKED => Translation::translate('Résiliation annulée : votre abonnement continue.'),
            self::NEXT_DUE_DATE => Translation::translate('à la prochaine échéance'),
            self::PERIOD_END => Translation::translate('terme de la période'),
        };
    }
}