<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

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
            self::CODE_SENT => Translation::translate('Un code de vérification a été envoyé à %s.'),
            self::EMAIL_VERIFIED => Translation::translate('Adresse e-mail vérifiée.'),
            self::CODE_RESENT => Translation::translate('Un nouveau code vient de vous être envoyé.'),
        };
    }
}