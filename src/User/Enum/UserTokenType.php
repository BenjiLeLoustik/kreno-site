<?php

namespace App\User\Enum;

enum UserTokenType: string
{
    case EMAIL_VERIFICATION = 'email_verification';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_CHANGE = 'email_change';
    case ACCOUNT_SETUP = 'account_setup';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'Vérification de l\'e-mail',
            self::PASSWORD_RESET => 'Mot de passe oublié',
            self::EMAIL_CHANGE => 'Changement d\'e-mail',
            self::ACCOUNT_SETUP => 'Création du mot de passe',
        };
    }
}