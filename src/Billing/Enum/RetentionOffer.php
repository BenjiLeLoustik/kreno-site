<?php

namespace App\Billing\Enum;

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
            self::PRICE => 'La remise est appliquée à votre abonnement.',
            self::VOLUME => 'Votre formule passera à la réservation à la prochaine échéance.',
            self::PAUSE => 'Votre abonnement est mis en pause.',
        };
    }
}