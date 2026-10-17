<?php

namespace App\Company\Enum;

enum SignupFlash
{
    case CODE_SENT;
    case EMAIL_VERIFIED;
    case CODE_RESENT;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CODE_SENT => 'Un code de vérification a été envoyé à %s.',
            self::EMAIL_VERIFIED => 'Adresse e-mail vérifiée.',
            self::CODE_RESENT => 'Un nouveau code vient de vous être envoyé.',
        };
    }
}