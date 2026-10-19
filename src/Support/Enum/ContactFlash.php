<?php

namespace App\Support\Enum;

use App\Shared\Helper\Translation;

enum ContactFlash
{
    case MESSAGE_SENT;

    public function text(): string
    {
        return match ($this) {
            self::MESSAGE_SENT => Translation::translate('Message envoyé. Nous vous répondons par e-mail.'),
        };
    }
}