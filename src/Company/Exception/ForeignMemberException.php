<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class ForeignMemberException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce membre ne fait pas partie de votre entreprise.');
    }
}