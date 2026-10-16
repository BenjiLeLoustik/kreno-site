<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PlanRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisissez d\'abord votre formule.');
    }
}