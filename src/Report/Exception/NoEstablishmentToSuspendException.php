<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoEstablishmentToSuspendException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucun établissement à suspendre.');
    }
}