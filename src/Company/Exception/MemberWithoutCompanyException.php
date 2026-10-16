<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class MemberWithoutCompanyException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune entreprise.');
    }
}