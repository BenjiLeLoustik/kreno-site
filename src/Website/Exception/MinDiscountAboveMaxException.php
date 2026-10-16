<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class MinDiscountAboveMaxException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La remise minimale doit être inférieure à la remise maximale.');
    }
}