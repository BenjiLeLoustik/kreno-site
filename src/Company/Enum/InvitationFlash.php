<?php

namespace App\Company\Enum;

enum InvitationFlash
{
    case WELCOME;

    public function text(): string
    {
        return match ($this) {
            self::WELCOME => 'Bienvenue dans l\'équipe !',
        };
    }
}