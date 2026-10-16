<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class ForeignEstablishmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cet établissement n\'appartient pas à l\'entreprise.');
    }
}