<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentNameRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le nom de l\'établissement est obligatoire.');
    }
}