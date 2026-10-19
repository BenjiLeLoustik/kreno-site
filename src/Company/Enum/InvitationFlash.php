<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum InvitationFlash
{
    case WELCOME;

    public function text(): string
    {
        return match ($this) {
            self::WELCOME => Translation::translate('Bienvenue dans l\'équipe !'),
        };
    }
}