<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class SepaMandateRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous devez accepter le mandat de prélèvement SEPA.');
    }
}