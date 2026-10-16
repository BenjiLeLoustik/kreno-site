<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class MissingRulesException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Toutes les règles de la plateforme doivent être renseignées.');
    }
}