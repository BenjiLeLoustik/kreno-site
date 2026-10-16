<?php

namespace App\User\Enum;

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
            self::WELCOME => 'Bienvenue %s ! Un e-mail de confirmation vient d\'être envoyé à %s.',
            self::EMAIL_VERIFIED => 'Ton adresse e-mail est confirmée. Merci !',
            self::VERIFICATION_SENT => 'Un nouveau lien de confirmation a été envoyé à %s.',
        };
    }
}