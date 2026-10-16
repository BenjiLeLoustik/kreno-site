<?php

namespace App\Booking\Enum;

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
            self::GUEST_ACCOUNT_CREATED => 'Ton compte Kréno est créé : un e-mail t\'attend à %s pour choisir ton mot de passe.',
            self::CANCELLED => 'Réservation annulée, sans frais.',
        };
    }
}