<?php

namespace App\Booking\Enum;

enum ProBookingFlash
{
    case HONORED;
    case NO_SHOW;
    case CANCELLED;

    public function text(): string
    {
        return match ($this) {
            self::HONORED => 'Rendez-vous marqué comme honoré.',
            self::NO_SHOW => 'Absence enregistrée et signalée à Kréno.',
            self::CANCELLED => 'Rendez-vous annulé. Le client est prévenu par e-mail.',
        };
    }
}