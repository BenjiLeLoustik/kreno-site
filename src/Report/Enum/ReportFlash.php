<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportFlash
{
    case SENT;
    case MESSAGE_SENT;
    case ESTABLISHMENT_SENT;
    case REPLY_SENT;

    public static function establishmentMessage(ReportDirection $direction): self
    {
        if ($direction === ReportDirection::FROM_CUSTOMER) {
            return self::REPLY_SENT;
        }

        return self::MESSAGE_SENT;
    }

    public function text(): string
    {
        return match ($this) {
            self::SENT => Translation::translate('Signalement envoyé. Nous revenons vers vous par e-mail sous 48 h.'),
            self::MESSAGE_SENT => Translation::translate('Message envoyé.'),
            self::ESTABLISHMENT_SENT => Translation::translate('Signalement envoyé. L\'équipe Kréno l\'examine sous 48 h.'),
            self::REPLY_SENT => Translation::translate('Réponse envoyée à l\'équipe Kréno.'),
        };
    }
}