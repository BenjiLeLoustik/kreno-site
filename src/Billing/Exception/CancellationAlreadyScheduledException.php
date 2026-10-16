<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class CancellationAlreadyScheduledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La résiliation est déjà enregistrée.');
    }
}