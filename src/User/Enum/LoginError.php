<?php

namespace App\User\Enum;

enum LoginError: string
{
    case INVALID_CREDENTIALS = 'Invalid credentials.';
    case INVALID_CSRF = 'Invalid CSRF token.';
    case TOO_MANY_ATTEMPTS = 'Too many failed login attempts';

    public static function message(?string $error): ?string
    {
        if ($error === null) {
            return null;
        }

        if (str_starts_with($error, self::TOO_MANY_ATTEMPTS->value)) {
            return self::TOO_MANY_ATTEMPTS->label();
        }

        return self::tryFrom($error)?->label() ?? $error;
    }

    public function label(): string
    {
        return match ($this) {
            self::INVALID_CREDENTIALS => 'Adresse e-mail ou mot de passe incorrect.',
            self::INVALID_CSRF => 'La page a expiré. Recharge-la et réessaie.',
            self::TOO_MANY_ATTEMPTS => 'Trop de tentatives. Réessaie dans une minute.',
        };
    }
}