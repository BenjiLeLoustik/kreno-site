<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class CompanyNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Entreprise introuvable.');
    }
}