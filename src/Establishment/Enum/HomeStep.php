<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum HomeStep: int
{
    case RELEASE = 1;
    case BOOK = 2;
    case PAY = 3;

    public function title(): string
    {
        return match ($this) {
            self::RELEASE => Translation::translate('Un pro libère un créneau'),
            self::BOOK => Translation::translate('Tu le réserves en deux clics'),
            self::PAY => Translation::translate('Tu paies sur place'),
        };
    }

    public function text(): string
    {
        return match ($this) {
            self::RELEASE => Translation::translate('Un client annule ? Le pro publie l’horaire libéré avec une remise, en quelques secondes.'),
            self::BOOK => Translation::translate('Tu reçois une alerte si c’est près de toi. Le créneau est bloqué à ton nom dès la réservation.'),
            self::PAY => Translation::translate('Pas de prépaiement : tu règles le prix réduit directement chez le pro, comme d’habitude.'),
        };
    }
}