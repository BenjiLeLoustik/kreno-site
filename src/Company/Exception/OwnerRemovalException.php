<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class OwnerRemovalException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le propriétaire ne peut pas être retiré de l\'entreprise.');
    }
}