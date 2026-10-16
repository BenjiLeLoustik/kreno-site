<?php

namespace App\Report\Enum;

enum ReportFlash
{
    case SENT;
    case MESSAGE_SENT;

    public function text(): string
    {
        return match ($this) {
            self::SENT => 'Signalement envoyé. Nous revenons vers vous par e-mail sous 48 h.',
            self::MESSAGE_SENT => 'Message envoyé.',
        };
    }
}