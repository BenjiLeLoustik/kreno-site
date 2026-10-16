<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class RegistrationEmailExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Un compte existe déjà avec cette adresse e-mail. Connectez-vous ou réinitialisez votre mot de passe.',
        );
    }
}