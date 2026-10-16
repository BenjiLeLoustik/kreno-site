<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidSirenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce numéro SIREN n\'est pas valide.');
    }
}