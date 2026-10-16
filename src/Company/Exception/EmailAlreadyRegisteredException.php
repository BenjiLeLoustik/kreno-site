<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EmailAlreadyRegisteredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Un compte existe déjà avec cette adresse : connectez-vous pour créer votre espace pro.',
        );
    }
}