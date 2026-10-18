<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum RetentionOffer: string
{
    case PRICE = 'price';
    case VOLUME = 'volume';
    case PAUSE = 'pause';

    public static function messageFor(?string $offer): ?string
    {
        if ($offer === null) {
            return null;
        }

        return self::tryFrom($offer)?->message();
    }

    public function message(): string
    {
        return match ($this) {
            self::PRICE => Translation::translate('La remise est appliquée à votre abonnement.'),
            self::VOLUME => Translation::translate('Votre formule passera à la réservation à la prochaine échéance.'),
            self::PAUSE => Translation::translate('Votre abonnement est mis en pause.'),
        };
    }
}