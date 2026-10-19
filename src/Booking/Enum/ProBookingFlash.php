<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

enum ProBookingFlash
{
    case HONORED;
    case NO_SHOW;
    case CANCELLED;

    public function text(): string
    {
        return match ($this) {
            self::HONORED => Translation::translate('Rendez-vous marqué comme honoré.'),
            self::NO_SHOW => Translation::translate('Absence enregistrée et signalée à Kréno.'),
            self::CANCELLED => Translation::translate('Rendez-vous annulé. Le client est prévenu par e-mail.'),
        };
    }
}