<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class StaffNotInEstablishmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce membre ne fait pas partie de l\'établissement.');
    }
}