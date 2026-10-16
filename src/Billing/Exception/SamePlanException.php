<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class SamePlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous êtes déjà sur cette formule.');
    }
}