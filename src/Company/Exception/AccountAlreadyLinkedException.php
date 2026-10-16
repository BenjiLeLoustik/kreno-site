<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class AccountAlreadyLinkedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Votre compte est déjà rattaché à une entreprise.');
    }
}