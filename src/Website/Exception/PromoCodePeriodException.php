<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PromoCodePeriodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La fin de validité du code doit être après son début.');
    }
}