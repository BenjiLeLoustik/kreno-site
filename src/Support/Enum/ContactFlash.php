<?php

namespace App\Support\Enum;

enum ContactFlash
{
    case MESSAGE_SENT;

    public function text(): string
    {
        return match ($this) {
            self::MESSAGE_SENT => 'Message envoyé. Nous vous répondons par e-mail.',
        };
    }
}