<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class RegistrationEmailExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un compte existe déjà avec cette adresse e-mail. Connectez-vous ou réinitialisez votre mot de passe.'),
        );
    }
}