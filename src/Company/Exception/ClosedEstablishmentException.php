<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class ClosedEstablishmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce SIRET correspond à un établissement fermé.');
    }
}