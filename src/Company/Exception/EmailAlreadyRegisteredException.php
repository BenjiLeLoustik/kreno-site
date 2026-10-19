<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class EmailAlreadyRegisteredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un compte existe déjà avec cette adresse : connectez-vous pour créer votre espace pro.'),
        );
    }
}