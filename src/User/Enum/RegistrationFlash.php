<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum RegistrationFlash
{
    case WELCOME;
    case EMAIL_VERIFIED;
    case VERIFICATION_SENT;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::WELCOME => Translation::translate('Bienvenue %s ! Un e-mail de confirmation vient d\'être envoyé à %s.'),
            self::EMAIL_VERIFIED => Translation::translate('Ton adresse e-mail est confirmée. Merci !'),
            self::VERIFICATION_SENT => Translation::translate('Un nouveau lien de confirmation a été envoyé à %s.'),
        };
    }
}