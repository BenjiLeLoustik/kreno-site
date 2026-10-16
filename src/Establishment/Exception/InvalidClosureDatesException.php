<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidClosureDatesException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Les dates de fermeture sont invalides.');
    }
}