<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class IncompletePlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Chaque formule active doit avoir un code et un nom.');
    }
}