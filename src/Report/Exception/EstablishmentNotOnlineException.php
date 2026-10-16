<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentNotOnlineException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet établissement n\'est pas en ligne.');
    }
}