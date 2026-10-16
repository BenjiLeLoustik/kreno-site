<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class EmailAlreadyVerifiedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Votre adresse e-mail est déjà vérifiée.');
    }
}