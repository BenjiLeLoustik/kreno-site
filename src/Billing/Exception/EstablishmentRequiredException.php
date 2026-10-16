<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Renseignez d\'abord votre établissement.');
    }
}