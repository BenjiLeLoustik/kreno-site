<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet établissement n\'est pas disponible.');
    }
}