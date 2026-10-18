<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum PasswordSpace: string
{
    case CLIENT = 'client';
    case PRO = 'pro';

    public function isPro(): bool
    {
        return $this === self::PRO;
    }

    public function forgotRoute(): string
    {
        return match ($this) {
            self::CLIENT => 'app_forgot_password',
            self::PRO => 'pro_forgot_password',
        };
    }

    public function resetRoute(): string
    {
        return match ($this) {
            self::CLIENT => 'app_reset_password',
            self::PRO => 'pro_reset_password',
        };
    }

    public function loginRoute(): string
    {
        return match ($this) {
            self::CLIENT => 'app_login',
            self::PRO => 'pro_login',
        };
    }

    public function resetFlash(): string
    {
        return match ($this) {
            self::CLIENT => Translation::translate('Ton mot de passe a été modifié. Tu peux te connecter.'),
            self::PRO => Translation::translate('Votre mot de passe a été modifié. Vous pouvez vous connecter.'),
        };
    }
}