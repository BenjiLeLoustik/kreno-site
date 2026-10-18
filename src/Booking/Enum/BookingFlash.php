<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

enum BookingFlash
{
    case GUEST_ACCOUNT_CREATED;
    case CANCELLED;

    public function text(string ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::GUEST_ACCOUNT_CREATED => Translation::translate('Ton compte Kréno est créé : un e-mail t\'attend à %s pour choisir ton mot de passe.'),
            self::CANCELLED => Translation::translate('Réservation annulée, sans frais.'),
        };
    }
}