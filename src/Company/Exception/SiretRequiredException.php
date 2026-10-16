<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SiretRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le SIRET est obligatoire.');
    }
}